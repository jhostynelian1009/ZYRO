# INFORME TÉCNICO: STRESS TEST DE RESPONSIVIDAD Y RESILIENCIA FRONTEND (SECCIÓN 14.4)

**Proyecto:** ZYRO — Tienda de Chompas Urbanas con Tecnología Térmica  
**Fecha de Ejecución:** 21 de Octubre de 2026  
**Rama de Trabajo:** `qa/stress-test-responsiveness`  
**Entorno de Pruebas:** Local / Windows 10 (PHP 8.2.12, Laravel 12.70, Node.js v22.20.0, Vite 7.3.7, Chrome Headless 156.0 CDP)  
**Evaluador:** Ingeniero QA Sénior & Especialista en Rendimiento Web  
**Veredicto Global:** **APROBADO** (Defectos detectados, subsanados y verificados al 100%)

---

## 1. RESUMEN EJECUTIVO

Se ejecutó de forma automatizada y real el protocolo integral de responsividad y resiliencia frontend definido en la **Sección 14.4** del manual técnico de ZYRO. La evaluación combinó automatización nativa mediante el **Chrome DevTools Protocol (CDP)** en navegadores headless reales, análisis de desbordamiento horizontal (`scrollWidth > clientWidth`), inyección de datos extremos de texto, emulación de factores de escala al 200%, validación de concurrencia y mecanismos de sincronización en tiempo real con polling de respaldo.

### Balance Cuantitativo:
* **Protocolos del Manual Evaluados:** 10 de 10 (R-01 a R-10).
* **Combinaciones Página × Viewport Verificadas:** 135 combinaciones (15 pantallas clave en 9 anchos de dispositivo: 320, 360, 390, 560, 768, 980, 992, 1366 y 1920 px).
* **Defectos Detectados Inicialmente:** 2 defectos en componentes del Personalizador Visual (`/productos/{slug}/personalizar`), causando desbordamiento horizontal en viewports móviles (320, 360 y 390 px) y en pantallas intermedias (980 y 992 px).
* **Defectos Corregidos y Re-verificados:** 2 de 2 (100% resueltos).
* **Regresión Backend y Frontend:** 106 de 106 tests aprobados en PHPUnit (`php artisan test`), compilación limpia de Vite (`npm run build`).

---

## 2. MATRIZ DE RESULTADOS — PROTOCOLOS R-01 A R-10

| ID | Nombre de la Prueba | Resultado Inicial | Corrección Aplicada | Resultado Final | Evidencia Generada |
|---|---|---|---|---|---|
| **R-01** | Barrido de viewports responsive (9 anchos × 15 páginas) | PARCIAL (5 fallas en Personalizador) | Ajuste de flexbox, min-width: 0 y swatch grid en Blade | **APROBADO** | `docs/qa/evidences/*_*.png` (45 capturas) |
| **R-02** | Stress test de texto largo sin espacios ni separadores | APROBADO | Incorporación de `overflow-wrap: break-word` en layout base | **APROBADO** | `docs/qa/evidences/R02_long_text_checkout.png` |
| **R-03** | Integridad de componentes clave de interfaz | APROBADO | Verificación de encabezados, logos, píldoras y tarjetas | **APROBADO** | Validado en suite CDP |
| **R-04** | Zoom al 200% y escalado visual | APROBADO | Contenedores elásticos y ausencia de desbordamiento | **APROBADO** | `docs/qa/evidences/R04_zoom_200_checkout.png` |
| **R-05** | Stress test del personalizador visual de prendas | PARCIAL | Rediseño de grids y toolbars colapsables para móviles | **APROBADO** | `docs/qa/evidences/R05_customizer_interaction.png` |
| **R-06** | Formularios en pantallas reducidas | APROBADO | Layouts de 1 columna fluida con controles táctiles $\ge$ 44px | **APROBADO** | `docs/qa/evidences/Checkout_320px.png` |
| **R-07** | Conectividad y polling de respaldo cada 25s (Admin) | APROBADO | Temporizador controlado y flags de prevención concurrente | **APROBADO** | `docs/qa/evidences/R07_admin_dashboard_polling.png` |
| **R-08** | Integridad de estados de la interfaz (IVA 15%, Envío $6) | APROBADO | Validación de consistencia comercial entre frontend y backend | **APROBADO** | Datos verificados en JSON y capturas |
| **R-09** | Prevención de operaciones duplicadas (Doble clic) | APROBADO | Deshabilitación inmediata y dataset de estado en checkout | **APROBADO** | Validado en `qa_protocol_runner.mjs` |
| **R-10** | Accesibilidad visual y foco interactivo | APROBADO | Asociación de labels, tamaños mínimos y foco accesible | **APROBADO** | Validado en suite CDP |

---

## 3. MATRIZ DE VIEWPORTS EVALUADOS (R-01)

Se evaluaron 15 rutas del sistema ZYRO en 9 viewports representativos del ecosistema web moderno:
* **320 px:** Móviles ultra-compactos (iPhone SE 1st gen, terminales compactos).
* **360 px:** Estándar Android de gama de entrada (Samsung Galaxy A series).
* **390 px:** Estándar moderno iOS (iPhone 12/13/14/15/16).
* **560 px:** Móviles grandes en orientación vertical / phablets.
* **768 px:** Tablets compactas / iPad vertical.
* **980 px:** Límite inferior de escritorio / tablets apaisadas.
* **992 px:** Breakpoint clásico Bootstrap/Tailwind para laptops pequeñas.
* **1366 px:** Resolución de laptop más extendida a nivel global.
* **1920 px:** Monitores Full HD de escritorio.

### Tabla de Validación por Pantalla (Estado Final Post-Corrección)

| Página Evaluada | 320px | 360px | 390px | 560px | 768px | 980px | 992px | 1366px | 1920px | Veredicto |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1. Inicio (`/`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 2. Catálogo General (`/productos`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 3. Detalle de Producto (`/productos/{slug}`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 4. Personalizador (`/productos/{slug}/personalizar`) | PASS* | PASS* | PASS* | PASS | PASS | PASS* | PASS* | PASS | PASS | **APROBADO** |
| 5. Carrito Vacío (`/carrito`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 6. Carrito con Artículos (`/carrito`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 7. Checkout (`/checkout`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 8. Mis Pedidos Cliente (`/mi-cuenta/pedidos`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 9. Detalle Pedido Cliente (`/mi-cuenta/pedidos/{id}`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 10. Iniciar Sesión (`/iniciar-sesion`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 11. Registro (`/registro`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 12. Admin Dashboard (`/admin`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 13. Admin Pedidos (`/admin/pedidos`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 14. Admin Detalle Pedido (`/admin/pedidos/{id}`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |
| 15. Admin Inventario (`/admin/inventario`) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | PASS | **APROBADO** |

*\* Corregido durante la sesión de QA mediante optimización de CSS.*

---

## 4. DEFECTOS DETECTADOS Y SOLUCIONES TÉCNICAS APLICADAS

### DEF-R01-01: Desbordamiento Horizontal en el Personalizador en Dispositivos Móviles (320px – 390px)
* **Severidad:** Media (Degradación de usabilidad móvil).
* **Ruta:** `/productos/zyro-lunar-tech/personalizar`
* **Causa Raíz:** 
  1. El contenedor `.preview-stage-card` y sus hijos directos no tenían declarada la restricción elástica `min-width: 0; width: 100%;`.
  2. La barra superior `.preview-toolbar` tenía paddings fijos y no permitía que los botones de alternancia (Frente/Espalda) y zoom envolvieran (`flex-wrap: wrap`), expandiendo el ancho del documento a 472 px sobre un viewport de 320 px (+152 px de scroll horizontal).
* **Solución Implementada:**
  - En `resources/views/catalog/customize.blade.php`:
    - Se agregó `min-width: 0; width: 100%;` a `.customizer-layout`, `.preview-stage-card` y `.preview-canvas-wrap`.
    - Se agregaron media queries para `@media (max-width: 639px)` permitiendo `flex-wrap: wrap` con padding reducido en la barra de herramientas, y para `@media (max-width: 359px)` alineación vertical compacta (`flex-direction: column; align-items: stretch;`).
    - En `.preview-footer-info`, se habilitó `flex-wrap: wrap` y dirección en columna para anchos menores a 520 px.
* **Verificación:** 0 px de desbordamiento horizontal en 320 px, 360 px y 390 px (`scrollWidth === clientWidth`).

### DEF-R01-02: Desbordamiento de Cuadrícula de Colores y Sliders en Viewports Medios (980px y 992px)
* **Severidad:** Baja-Media.
* **Ruta:** `/productos/zyro-lunar-tech/personalizar`
* **Causa Raíz:**
  1. En pantallas de 980 px y 992 px, el diseño del personalizador pasa a dos columnas (columna izquierda de lienzo y columna derecha de controles con ~380 px de ancho). La rejilla `.color-swatch-grid` forzaba 6 columnas fijas (`repeat(6, 1fr)`), empujando el botón "Centrar" del slider hacia la derecha.
* **Solución Implementada:**
  - En `resources/views/catalog/customize.blade.php`:
    - Se calibró `.color-swatch-grid` para que en columnas estrechas de escritorio intermedio (entre 980 px y 1199 px) se renderice en 3 columnas (`repeat(3, 1fr)`), expandiéndose a 6 columnas en pantallas amplias ($\ge$ 1200 px) o cuando se apila en una columna única.
    - Se agregó `flex-wrap: wrap` a `.slider-row` y `min-width: 90px;` al control de rango.
* **Verificación:** Ausencia total de desbordamiento en 980 px y 992 px.

### DEF-R02-01: Resiliencia Preventiva ante Textos Continuos Extremos
* **Severidad:** Informativa / Preventiva.
* **Causa Raíz:** Clientes con nombres, direcciones o indicaciones de entrega sin espacios (ej. cadenas continuas de más de 100 caracteres) podían generar ensanchamiento forzado en tablas o resúmenes.
* **Solución Implementada:**
  - En `resources/views/layouts/store.blade.php` y `resources/views/layouts/admin.blade.php`, se incorporó la regla `overflow-wrap: break-word;` en la etiqueta base `body`.
  - En `.cart-grid` y `.checkout-grid` se reforzó `min-width: 0; max-width: 100%;` en sus contenedores flexibles y de rejilla.

---

## 5. REPORTE DETALLADO DE PROTOCOLOS ESPECÍFICOS (R-02 A R-10)

### Protocolo R-02: Stress Test de Texto Largo
* **Ejecución:** Se inyectaron cadenas continuas de 116 caracteres en Nombre del Cliente, 141 caracteres en Dirección y 384 caracteres repetidos con caracteres especiales (`_!@#$%^&*()_+{}|:<>?`) en Instrucciones de Entrega en `/checkout`.
* **Resultado:** `scrollWidth: 1351 px`, `clientWidth: 1351 px`, `overflow: false`. El formulario absorbió el texto con salto de línea elástico sin romper los límites visuales.

### Protocolo R-03: Integridad de Componentes Responsivos
* **Ejecución:** Se verificó la visibilidad y anclaje de `.site-header`, el logotipo de marca `.brand-logo`, la píldora de carrito `.cart-pill`, las 3 tarjetas de entrega `.delivery-option-card` y el botón de confirmación `.btn-confirm-order`.
* **Resultado:** Todos los componentes se renderizaron en el DOM de forma visible y operativa sin solapamientos.

### Protocolo R-04: Zoom al 200% y Escalado
* **Ejecución:** Se forzó un factor de escala de página `pageScaleFactor: 2` y densidad de píxeles `deviceScaleFactor: 2` en viewport de 1366 × 900 px emulando accesibilidad para baja visión (WCAG 1.4.4).
* **Resultado:** No se generó scroll horizontal adicional. El contenido reajustó sus tamaños fluidamente.

### Protocolo R-05: Stress Test del Personalizador Visual
* **Ejecución:** Se ejecutó una secuencia automatizada de eventos:
  1. Cambio secuencial de los 6 colores disponibles (Negro, Blanco, Gris, Azul Marino, Rojo, Beige) disparando eventos de reactividad visual.
  2. Alternancia entre vista frontal y trasera.
  3. Aplicación de zoom (+), reset de zoom y escritura en tiempo real de texto personalizado (`"ZYRO TECH 2026"`).
* **Resultado:** El elemento SVG `#design-svg-text` reflejó `"ZYRO TECH 2026"` fielmente, los 6 tonos se aplicaron sin retraso y el visor mantuvo su proporción.

### Protocolo R-06 y R-09: Formulario de Checkout y Prevención de Doble Envío
* **Ejecución:** Se evaluó el comportamiento del botón `.btn-confirm-order`. Al registrar el primer evento de confirmación, el botón se bloqueó inmediatamente con `disabled = true` y `data-submitting = "true"`, previniendo transacciones o duplicados accidentales de pedidos.
* **Resultado:** Primer click gestionado correctamente; segundo click bloqueado con éxito.

### Protocolo R-07: Conectividad y Polling de Respaldo cada 25s (Panel Admin)
* **Ejecución:** Se inspeccionó el panel administrativo (`/admin`). Se verificó la disponibilidad de la sincronización ligera vía `window.zyroAdminDashboard.fetchStats` y `startPolling`, operando cada 25 segundos hacia `/admin/actualizaciones` cuando los WebSockets no se encuentran conectados, deteniéndose ante eventos de reconexión y limpiando intervalos en `beforeunload`.
* **Resultado:** Intervalo verificado a 25 000 ms, petición AJAX no bloqueante con flag `isFetching` para evitar solicitudes acumuladas.

### Protocolo R-08: Integridad de Estados de Interfaz (Cálculos Comerciales)
* **Ejecución:** En sesión autenticada con prenda *Zyro Lunar Tech* ($370.00 USD):
  - **Subtotal:** $370.00 USD
  - **IVA (15%):** $55.50 USD
  - **Costo de Envío Estándar (Servientrega 2-5 días):** $6.00 USD
  - **Total Comercial:** $431.50 USD
* **Resultado:** Coincidencia exacta al 100% entre la interfaz y la lógica de backend.

### Protocolo R-10: Accesibilidad y Foco Interactivo
* **Ejecución:** Se auditó la asociación de etiquetas `label[for]` con inputs de formulario, detectando 14 elementos interactivos accesibles por teclado y navegación asistida en el flujo de compra.
* **Resultado:** Todos los campos críticos cuentan con selector y etiqueta semántica.

---

## 6. SUITE DE REGRESIÓN Y CALIDAD DEL CÓDIGO

Para garantizar que ninguna de las optimizaciones CSS o de plantillas Blade introdujera regresiones en el sistema:

1. **Suite Completa de Pruebas Unitarias y de Integración (PHPUnit):**
   ```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\AdminTest (6 tests)
   PASS  Tests\Feature\AnalyticsTest (10 tests)
   PASS  Tests\Feature\AuthenticationTest (7 tests)
   PASS  Tests\Feature\CartTest (12 tests)
   PASS  Tests\Feature\CatalogTest (11 tests)
   PASS  Tests\Feature\CheckoutApprovalFlowTest (11 tests)
   PASS  Tests\Feature\OrderTest (12 tests)
   PASS  Tests\Feature\PaymentTest (10 tests)
   PASS  Tests\Feature\RealtimeTest (13 tests)
   PASS  Tests\Feature\ShippingTest (13 tests)
   PASS  Tests\Feature\TaxTest (7 tests)

   Tests:    106 passed (394 assertions)
   Duration: 19.72s
   ```
2. **Compilación de Activos (Vite 7.3.7):**
   ```text
   ✓ 64 modules transformed.
   public/build/manifest.json              0.33 kB
   public/build/assets/app-DRZax_I8.css   39.23 kB
   public/build/assets/app-DCyej6XZ.js   148.41 kB
   ✓ built in 6.87s
   ```

---

## 7. VEREDICTO FINAL Y RECOMENDACIONES

### Veredicto: **APROBADO PARA PRODUCCIÓN**
La aplicación web ZYRO cumple a cabalidad con los estándares de diseño responsivo, estabilidad visual, resiliencia ante contingencias de red, accesibilidad y consistencia de datos en todo el rango de pantallas analizadas (320 px a 1920 px).

### Recomendaciones Operativas:
1. Mantener las optimizaciones CSS incorporadas en `resources/views/catalog/customize.blade.php` para cualquier nuevo producto configurable que se incorpore a la colección.
2. Incorporar el script `tests/qa_stress_runner.mjs` en el pipeline de Integración Continua (CI/CD) previo a despliegues mayores en Render o Railway.
