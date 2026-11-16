import { spawn } from 'child_process';
import { writeFileSync, mkdirSync } from 'fs';
import path from 'path';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const USER_DATA_DIR = 'C:\\xampp\\htdocs\\ZYRO\\storage\\qa_chrome_runner';
const BASE_URL = 'http://127.0.0.1:8000';

class CDPClient {
    constructor(wsUrl) {
        this.wsUrl = wsUrl;
        this.ws = null;
        this.id = 1;
        this.callbacks = new Map();
        this.events = new Map();
    }

    async connect() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(this.wsUrl);
            this.ws.onopen = () => resolve();
            this.ws.onerror = (err) => reject(err);
            this.ws.onmessage = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id && this.callbacks.has(msg.id)) {
                    const cb = this.callbacks.get(msg.id);
                    this.callbacks.delete(msg.id);
                    if (msg.error) {
                        cb.reject(new Error(msg.error.message));
                    } else {
                        cb.resolve(msg.result);
                    }
                } else if (msg.method) {
                    const handlers = this.events.get(msg.method) || [];
                    handlers.forEach(h => h(msg.params));
                }
            };
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = this.id++;
            this.callbacks.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    on(method, handler) {
        if (!this.events.has(method)) this.events.set(method, []);
        this.events.get(method).push(handler);
    }

    close() {
        if (this.ws) {
            try { this.ws.close(); } catch(e) {}
        }
    }
}

async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function setViewport(client, width, height = 900) {
    await client.send('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 1,
        mobile: width < 768
    });
    await sleep(200);
}

async function checkOverflow(client) {
    const expr = `(() => {
        const docEl = document.documentElement;
        const scrollW = docEl.scrollWidth;
        const clientW = docEl.clientWidth;
        const innerW = window.innerWidth;
        const hasOverflow = scrollW > clientW + 1;
        let offenders = [];
        if (hasOverflow) {
            const all = document.querySelectorAll('*');
            for (let el of all) {
                const rect = el.getBoundingClientRect();
                if (rect.right > clientW + 1.5) {
                    offenders.push({
                        tag: el.tagName,
                        id: el.id || null,
                        className: (typeof el.className === 'string' ? el.className : '').slice(0, 80),
                        rectRight: Math.round(rect.right),
                        rectWidth: Math.round(rect.width)
                    });
                }
            }
        }
        return {
            scrollWidth: scrollW,
            clientWidth: clientW,
            hasOverflow: hasOverflow,
            offenderCount: offenders.length,
            offenders: offenders.slice(0, 5)
        };
    })()`;
    const res = await client.send('Runtime.evaluate', { expression: expr, returnByValue: true });
    return res.result.value;
}

async function captureScreenshot(client, filepath) {
    const res = await client.send('Page.captureScreenshot', { format: 'png' });
    const buf = Buffer.from(res.data, 'base64');
    writeFileSync(filepath, buf);
}

async function main() {
    mkdirSync('docs/qa/evidences', { recursive: true });
    console.log('=== ZYRO RESPONSIVENESS STRESS TEST RUNNER ===');
    console.log('Launching headless Chrome...');

    const chrome = spawn(CHROME_PATH, [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--window-size=1366,900',
        '--user-data-dir=' + USER_DATA_DIR,
        'about:blank'
    ]);

    await sleep(2000);

    const testResults = {
        r01_viewports: [],
        defects: []
    };

    try {
        const verRes = await fetch('http://127.0.0.1:9222/json/list');
        const list = await verRes.json();
        const target = list.find(t => t.type === 'page');
        if (!target) throw new Error('No page target found');

        const client = new CDPClient(target.webSocketDebuggerUrl);
        await client.connect();
        await client.send('Page.enable');
        await client.send('DOM.enable');

        const VIEWPORTS = [320, 360, 390, 560, 768, 980, 992, 1366, 1920];
        
        // 1. PUBLIC PAGES
        const publicPages = [
            { name: 'Home', path: '/' },
            { name: 'Catalog', path: '/productos' },
            { name: 'ProductDetail', path: '/productos/zyro-lunar-tech' },
            { name: 'Customizer', path: '/productos/zyro-lunar-tech/personalizar' },
            { name: 'Login', path: '/iniciar-sesion' },
            { name: 'Register', path: '/registro' }
        ];

        console.log('\n--- FASE 1: Viewports en Páginas Públicas ---');
        for (const page of publicPages) {
            console.log(`\nProbando página: ${page.name} (${page.path})`);
            await client.send('Page.navigate', { url: BASE_URL + page.path });
            await sleep(1200);

            for (const vp of VIEWPORTS) {
                await setViewport(client, vp, 900);
                await sleep(300);
                const check = await checkOverflow(client);
                const resultItem = {
                    page: page.name,
                    path: page.path,
                    viewport: vp,
                    ...check
                };
                testResults.r01_viewports.push(resultItem);

                const statusStr = check.hasOverflow ? '❌ OVERFLOW' : '✅ OK';
                console.log(`  [${vp}px] ${statusStr} (scroll: ${check.scrollWidth}px, client: ${check.clientWidth}px)`);

                if (check.hasOverflow) {
                    const defectId = `DEF-R01-${page.name}-${vp}`;
                    console.log(`    ⚠️ Defecto detectado: ${JSON.stringify(check.offenders)}`);
                    testResults.defects.push({
                        id: defectId,
                        page: page.name,
                        viewport: vp,
                        offenders: check.offenders
                    });
                    await captureScreenshot(client, `docs/qa/evidences/overflow_${page.name}_${vp}px.png`);
                }

                // Guardar evidencia representativa para viewports clave (320, 768, 1366)
                if ([320, 768, 1366].includes(vp)) {
                    await captureScreenshot(client, `docs/qa/evidences/${page.name}_${vp}px.png`);
                }
            }
        }

        // 2. AUTHENTICATION & CUSTOMER FLOW (Login as cliente@zyro.test, Cart, Checkout, Order Show)
        console.log('\n--- FASE 2: Autenticación de Cliente y Flujo de Carrito/Checkout ---');
        await client.send('Page.navigate', { url: BASE_URL + '/iniciar-sesion' });
        await sleep(1000);

        // Fill login form
        await client.send('Runtime.evaluate', {
            expression: `(() => {
                document.querySelector('input[name="email"]').value = 'cliente@zyro.test';
                document.querySelector('input[name="password"]').value = 'password';
                document.querySelector('form').submit();
            })()`
        });
        await sleep(1500);

        // Add a product to cart via cart.store
        await client.send('Page.navigate', { url: BASE_URL + '/productos/zyro-lunar-tech' });
        await sleep(1000);
        await client.send('Runtime.evaluate', {
            expression: `(() => {
                const addForm = document.querySelector('form[action*="/carrito/agregar"]');
                if (addForm) addForm.submit();
            })()`
        });
        await sleep(1500);

        const customerPages = [
            { name: 'CartWithItems', path: '/carrito' },
            { name: 'Checkout', path: '/checkout' },
            { name: 'CustomerOrders', path: '/mi-cuenta/pedidos' },
            { name: 'CustomerOrderDetail', path: '/mi-cuenta/pedidos/1' }
        ];

        for (const page of customerPages) {
            console.log(`\nProbando página cliente: ${page.name} (${page.path})`);
            await client.send('Page.navigate', { url: BASE_URL + page.path });
            await sleep(1200);

            for (const vp of VIEWPORTS) {
                await setViewport(client, vp, 900);
                await sleep(300);
                const check = await checkOverflow(client);
                testResults.r01_viewports.push({
                    page: page.name,
                    path: page.path,
                    viewport: vp,
                    ...check
                });

                const statusStr = check.hasOverflow ? '❌ OVERFLOW' : '✅ OK';
                console.log(`  [${vp}px] ${statusStr} (scroll: ${check.scrollWidth}px, client: ${check.clientWidth}px)`);

                if (check.hasOverflow) {
                    console.log(`    ⚠️ Defecto detectado: ${JSON.stringify(check.offenders)}`);
                    testResults.defects.push({
                        id: `DEF-R01-${page.name}-${vp}`,
                        page: page.name,
                        viewport: vp,
                        offenders: check.offenders
                    });
                    await captureScreenshot(client, `docs/qa/evidences/overflow_${page.name}_${vp}px.png`);
                }

                if ([320, 768, 1366].includes(vp)) {
                    await captureScreenshot(client, `docs/qa/evidences/${page.name}_${vp}px.png`);
                }
            }
        }

        // 3. ADMIN FLOW (Logout and login as admin@zyro.test)
        console.log('\n--- FASE 3: Autenticación Admin y Panel Administrativo ---');
        await client.send('Page.navigate', { url: BASE_URL + '/iniciar-sesion' });
        await sleep(800);

        // Fill admin credentials
        await client.send('Runtime.evaluate', {
            expression: `(() => {
                document.querySelector('input[name="email"]').value = 'admin@zyro.test';
                document.querySelector('input[name="password"]').value = 'password';
                document.querySelector('form').submit();
            })()`
        });
        await sleep(1500);

        const adminPages = [
            { name: 'AdminDashboard', path: '/admin' },
            { name: 'AdminOrders', path: '/admin/pedidos' },
            { name: 'AdminOrderDetail', path: '/admin/pedidos/1' },
            { name: 'AdminInventory', path: '/admin/inventario' },
            { name: 'AdminAnalytics', path: '/admin/analitica' }
        ];

        for (const page of adminPages) {
            console.log(`\nProbando página admin: ${page.name} (${page.path})`);
            await client.send('Page.navigate', { url: BASE_URL + page.path });
            await sleep(1200);

            for (const vp of VIEWPORTS) {
                await setViewport(client, vp, 900);
                await sleep(300);
                const check = await checkOverflow(client);
                testResults.r01_viewports.push({
                    page: page.name,
                    path: page.path,
                    viewport: vp,
                    ...check
                });

                const statusStr = check.hasOverflow ? '❌ OVERFLOW' : '✅ OK';
                console.log(`  [${vp}px] ${statusStr} (scroll: ${check.scrollWidth}px, client: ${check.clientWidth}px)`);

                if (check.hasOverflow) {
                    console.log(`    ⚠️ Defecto detectado: ${JSON.stringify(check.offenders)}`);
                    testResults.defects.push({
                        id: `DEF-R01-${page.name}-${vp}`,
                        page: page.name,
                        viewport: vp,
                        offenders: check.offenders
                    });
                    await captureScreenshot(client, `docs/qa/evidences/overflow_${page.name}_${vp}px.png`);
                }

                if ([320, 768, 1366].includes(vp)) {
                    await captureScreenshot(client, `docs/qa/evidences/${page.name}_${vp}px.png`);
                }
            }
        }

        // Write summary output
        writeFileSync('docs/qa/r01_initial_run.json', JSON.stringify(testResults, null, 2));
        console.log('\nResultados R-01 guardados en docs/qa/r01_initial_run.json');
        console.log(`Total pruebas de viewports: ${testResults.r01_viewports.length}`);
        console.log(`Total defectos detectados: ${testResults.defects.length}`);

        client.close();
    } finally {
        chrome.kill();
        console.log('Chrome runner finalizado.');
    }
}

main().catch(err => {
    console.error('Fatal runner error:', err);
    process.exit(1);
});
