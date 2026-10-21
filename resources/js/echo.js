import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const isHttps   = window.location.protocol === 'https:' || (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
const wsHost    = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const wsPort    = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : (isHttps ? 443 : 8080);

window.zyroRealtime = {
    isAvailable: false,
    state: 'uninitialized',
    listeners: new Set(),
};

if (reverbKey) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: wsHost,
            wsPort: wsPort,
            wssPort: wsPort,
            forceTLS: isHttps,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        });

        if (window.Echo.connector?.pusher) {
            const connection = window.Echo.connector.pusher.connection;

            connection.bind('state_change', (states) => {
                window.zyroRealtime.state = states.current;
                window.zyroRealtime.isAvailable = states.current === 'connected';

                window.dispatchEvent(new CustomEvent('zyro:realtime-state', {
                    detail: {
                        state: states.current,
                        previous: states.previous,
                    },
                }));
            });

            connection.bind('connected', () => {
                console.info('[ZYRO Realtime] Conexión WebSocket establecida exitosamente vía Reverb.');
            });

            connection.bind('disconnected', () => {
                console.warn('[ZYRO Realtime] WebSocket desconectado. Activando modo de respaldo seguro.');
            });

            connection.bind('error', (err) => {
                console.warn('[ZYRO Realtime] Notificación de conexión WebSocket:', err);
            });
        }
    } catch (e) {
        console.warn('[ZYRO Realtime] No se pudo inicializar Laravel Echo:', e);
        window.Echo = null;
    }
} else {
    console.info('[ZYRO Realtime] VITE_REVERB_APP_KEY no configurado. Operando con polling de respaldo.');
    window.Echo = null;
}

/**
 * Helper global para suscribirse de forma segura sin arrojar excepciones si Echo no está activo.
 */
window.zyroSubscribe = function (channelType, channelName) {
    if (!window.Echo) {
        return {
            listen: () => ({ listen: () => {} }),
            stopListening: () => {},
        };
    }

    try {
        if (channelType === 'private') {
            return window.Echo.private(channelName);
        }
        return window.Echo.channel(channelName);
    } catch (err) {
        console.warn(`[ZYRO Realtime] Error suscribiendo a canal ${channelName}:`, err);
        return {
            listen: () => ({ listen: () => {} }),
            stopListening: () => {},
        };
    }
};

/**
 * Helper global para notificaciones visuales discretas (Toast).
 */
window.zyroToast = function (message, type = 'info', linkUrl = null, linkText = 'Ver detalle') {
    let container = document.getElementById('zyro-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'zyro-toast-container';
        container.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:99999;display:flex;flex-direction:column;gap:0.75rem;pointer-events:none;max-width:380px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = 'pointer-events:auto;background:#090909;color:#FFFFFF;padding:0.875rem 1.15rem;border-radius:2px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.3);font-size:0.8125rem;line-height:1.45;border-left:3px solid #10B981;display:flex;flex-direction:column;gap:0.35rem;animation:zyroSlideIn 0.25s cubic-bezier(0.16,1,0.3,1);';

    if (type === 'amber') {
        toast.style.borderLeftColor = '#F59E0B';
    } else if (type === 'blue') {
        toast.style.borderLeftColor = '#315DDB';
    } else if (type === 'red') {
        toast.style.borderLeftColor = '#EF4444';
    }

    let linkHtml = '';
    if (linkUrl) {
        linkHtml = `<a href="${linkUrl}" style="color:#A7F3D0;text-decoration:underline;font-weight:600;font-size:0.75rem;margin-top:0.2rem;display:inline-block;">${linkText} &rarr;</a>`;
    }

    toast.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;font-size:1rem;line-height:1;padding:0;">&times;</button>
        </div>
        ${linkHtml}
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.transform = 'translateY(8px)';
        setTimeout(() => toast.remove(), 300);
    }, 6000);
};

// Estilo de animación keyframe para toast si no existe
if (!document.getElementById('zyro-toast-styles')) {
    const style = document.createElement('style');
    style.id = 'zyro-toast-styles';
    style.textContent = `
        @keyframes zyroSlideIn {
            from { transform: translateY(16px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}
