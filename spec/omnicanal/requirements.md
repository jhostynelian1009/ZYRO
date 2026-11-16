# Especificación de Requisitos de Software (SRS) — ZYRO Omnicanal (Alcance Refinado MVP)
**Metodología:** PolkDev | **Enfoque:** Spec as a Skill  
**Proyecto:** ZYRO — Comercio Electrónico Omnicanal  
**Rama:** `feature/zyro-omnicanal`  
**Aprobación de Producto:** Felipe García (Product Owner)  
**Fase:** F1 — Alcance Refinado MVP (Pragmático y Demostrable)  
**Estado:** Especificación Ajustada — Lista para F2  

---

## 1. Visión y Enfoque Pragmático del MVP

El objetivo de esta fase es implementar una **omnicanalidad demostrable, ágil y de alto impacto operativo**, aprovechando al máximo la arquitectura existente en Laravel 12 sin introducir sobreingeniería empresarial ni duplicar subsistemas.

### 1.1 Principio de Reutilización Máxima
- **Inventario Unificado:** Se utiliza la tabla [`products`](file:///C:/xampp/htdocs/ZYRO/app/Models/Product.php) y su columna `stock` existente con el mecanismo de bloqueo transaccional ya probado (`lockForUpdate()`). No se crean almacenes separados ni modelos complejos de variantes múltiples para este MVP.
- **Núcleo de Pedidos Central:** Todas las ventas (Web, WhatsApp y Punto Físico) se registran en la tabla [`orders`](file:///C:/xampp/htdocs/ZYRO/app/Models/Order.php) y [`order_items`](file:///C:/xampp/htdocs/ZYRO/app/Models/OrderItem.php) con una numeración correlativa única (`ZYR-XXXX`).
- **Canal de Comunicación Ágil:** Integración mediante enlaces universales dinámicos `wa.me` con SKU y pedido precargados, sin dependencia de Meta Cloud API.
- **Gestión Simple de Cambios:** Flujo de 3 estados (`recibida`, `en_revision`, `resuelta`) con inspección física previa antes de cualquier reingreso al inventario vendible.

---

## 2. Especificación de los Cuatro Módulos Prioritarios

### 2.1 K-001: Registro de Ventas por Canal (Panel Administrativo)
Permite al personal de ZYRO registrar manualmente compras presenciales o asistidas directamente en `/admin/ventas/crear`.

- **RF-K001-01 Selección / Registro de Cliente:**
  - El operador puede buscar un cliente existente por nombre, correo o teléfono, o registrar rápidamente uno nuevo (nombre, teléfono, correo, ciudad/dirección).
- **RF-K001-02 Selección de Productos, Tallas y Cantidades:**
  - Selector dinámico de prendas del catálogo ZYRO con visualización en vivo de disponibilidad (`stock`).
  - Validación en servidor: cantidad $> 0$ y menor o igual al stock disponible.
- **RF-K001-03 Selección de Canal de Origen:**
  - Atributo obligatorio `channel_origin`:
    - `punto_fisico` (Venta presencial en local de Guayaquil).
    - `whatsapp` (Venta asistida por chat de WhatsApp Business).
- **RF-K001-04 Selección de Pago y Entrega:**
  - Métodos de pago compatibles: Efectivo, Transferencia Bancaria, Tarjeta / Enlace Digital (compatible con el esquema académico actual).
  - Modalidades de entrega: Entrega inmediata en mostrador, Retiro posterior en local, Envío estándar local ($6), Envío rápido local ($8).
- **RF-K001-05 Generación de Identificador Único:**
  - Generación del identificador único oficial (`ZYR-XXXX`).
- **RF-K001-06 Descuento Transaccional Inmediato de Inventario:**
  - La venta descuenta de forma atómica el stock en `products.stock` utilizando el mismo mecanismo transaccional de [`CheckoutService`](file:///C:/xampp/htdocs/ZYRO/app/Services/CheckoutService.php) con `lockForUpdate()`.
  - Se prohíbe crear una segunda lógica de inventario. El nuevo saldo se refleja de inmediato en la tienda web pública.
- **RF-K001-07 Trazabilidad del Responsable:**
  - Se guarda el ID del usuario administrativo autenticado (`created_by` / `admin_id`) que registró la venta.

---

### 2.2 K-002: Gestión Unificada de Pedidos
Ampliación del módulo administrativo existente ([`Admin\OrderController`](file:///C:/xampp/htdocs/ZYRO/app/Http/Controllers/Admin/OrderController.php) y vistas en `resources/views/admin/orders/`).

- **RF-K002-01 Repositorio Único de Pedidos:**
  - Concentra en una sola vista todos los pedidos procedentes de:
    - `web` (Autoservicio online).
    - `whatsapp` (Venta asistida por mensajería).
    - `punto_fisico` (Venta presencial en Guayaquil).
- **RF-K002-02 Información Visible en Listado:**
  - Número de pedido (`order_number`).
  - Nombre del cliente y contacto.
  - Resumen de productos y unidades.
  - Canal de origen con distintivo visual (badge: Web, WhatsApp, Punto Físico).
  - Método y estado del pago (`pending`, `paid`, `rejected`).
  - Modalidad de entrega (Mostrador, Retiro, Envío).
  - Estado del pedido (`pending_approval`, `approved`, `paid`, `cancelled`).
  - Fecha y hora de creación.
- **RF-K002-03 Filtros Avanzados:**
  - Filtro por Canal de Origen (`web`, `whatsapp`, `punto_fisico`).
  - Filtro por Estado del Pedido.
  - Filtro por Rango de Fechas.
  - Búsqueda por texto (número de pedido, nombre o correo del cliente).
- **RF-K002-04 Detalle Completo del Pedido:**
  - Vista integral del pedido mostrando desglose de productos, tallas, personalizaciones adjuntas, desglose financiero (subtotal, IVA 15%, envío), historial de cambios de estado y bitácora de atención.
- **RF-K002-05 Continuidad Multicanal sin Duplicados:**
  - Si un pedido se origina en WhatsApp para ser retirado o pagado en el Punto Físico, conserva exactamente el mismo `order_number`.
  - La confirmación en caja actualiza el pedido existente; **bajo ninguna circunstancia se crea un segundo pedido**.
- **RF-K002-06 Retrocompatibilidad:**
  - Todos los pedidos históricos existentes creados en el primer parcial se visualizan correctamente, asignándoles por defecto el canal `web`.

---

### 2.3 K-003: Comunicación Omnicanal Asistida
Facilita la interacción cliente-tienda mediante enlaces universales dinámicos y registro de atenciones.

- **RF-K003-01 Botones Dinámicos de WhatsApp (`wa.me`):**
  - **En Ficha y Tarjetas de Producto:** Genera enlace directo:  
    `https://wa.me/593XXXXXXXXX?text=Hola,%20quiero%20consultar%20por%20la%20chompa%20{nombre}%20talla%20{talla}.%20Referencia:%20{slug}`
  - **En Detalle de Pedido del Cliente (`/mi-cuenta/pedidos/{order}`):** Genera enlace de soporte:  
    `https://wa.me/593XXXXXXXXX?text=Hola,%20quisiera%20consultar%20sobre%20mi%20pedido%20{order_number}`
  - **En Detalle de Pedido Administrativo (`/admin/pedidos/{order}`):** Botón para que el asesor contacte directamente al cliente al WhatsApp registrado con un clic.
- **RF-K003-02 Historial de Atención del Pedido:**
  - En la vista administrativa del pedido, sección para registrar notas de seguimiento e interacciones:
    - Fecha y hora.
    - Canal (`WhatsApp`, `Llamada`, `Presencial`, `Correo`).
    - Usuario responsable (asesor administrativo).
    - Tipo de interacción (`Consulta de talla`, `Coordinación de entrega`, `Comprobante de pago`, `Reclamo`, `Otro`).
    - Descripción y acuerdos de seguimiento.
- **RF-K003-03 Transparencia Técnica:**
  - Esta funcionalidad opera de forma nativa e inmediata mediante enlaces web universales. No se simula ni se asume integración operativa con Meta Cloud API en esta versión.

---

## 2.4 K-004: Cambios, Devoluciones y Posventa
Módulo de atención posventa en cumplimiento de los derechos legales del consumidor (Art. 45 LODC Ecuador).

- **RF-K004-01 Solicitud en Autoservicio del Cliente:**
  - En `/mi-cuenta/pedidos`, opción visible: «Solicitar cambio o devolución».
  - El cliente completa un formulario estructurado:
    1. Selección del pedido propio.
    2. Selección del producto específico y talla.
    3. Motivo (`Cambio de talla`, `Preferencia de color`, `Defecto de fábrica`, `Discrepancia con lo pedido`).
    4. Descripción detallada del problema.
    5. Solución solicitada (`Cambio por otra talla`, `Cambio por otro producto`, `Devolución de dinero`).
    6. Envío y generación automática de número de solicitud (`DEV-XXXX`).
- **RF-K004-02 Módulo Administrativo de Gestión (`/admin/solicitudes`):**
  - Bandeja centralizada de solicitudes con tres estados base:
    1. **Recibida:** Solicitud ingresada por el cliente, pendiente de recepción de la prenda.
    2. **En revisión:** Prenda entregada físicamente en el punto físico o recibida por courier; en proceso de inspección.
    3. **Resuelta:** Dictamen ejecutado (cambio entregado o reembolso tramitado) o rechazada justificadamente.
- **RF-K004-03 Registro de Inspección Técnica de Calidad:**
  - Antes de resolver, el responsable debe asentar obligatoriamente:
    - Responsable de la revisión (`reviewer_id`).
    - Producto recibido físicamente y fecha.
    - Estado de inspección (`Óptimo con etiquetas`, `Usado limpio`, `Falla de fábrica comprobada`, `No apto / Daño por uso`).
    - Producto de reemplazo asignado (si aplica cambio).
    - Decisión administrativa debidamente motivada.
    - Historial de cambios de estado con fecha y notas.
- **RF-K004-04 Movimientos de Inventario Condicionados:**
  - **Regla Estricta:** La prenda devuelta **jamás vuelve al stock vendible de forma automática**.
  - Si la inspección dictamina que la prenda está en estado óptimo nuevo y se aprueba su reincorporación, el operador marca explícitamente el reingreso al inventario (`restock`), incrementando `products.stock` mediante transacción segura.
  - Si se entrega una prenda de reemplazo, se descuenta de inmediato del stock correspondiente.
- **RF-K004-05 Respeto a las Políticas Aprobadas en F1:**
  - Se mantiene el plazo comercial de 15 días hábiles contados desde la entrega para solicitudes de cambio o retracto.
  - La garantía legal por defectos de fábrica no queda restringida por el plazo de cambios por preferencia.

---

## 3. Delimitación Estricta del Alcance (Exclusiones para F2)

| Componente / Característica | Estado en este MVP | Justificación de Alcance |
| :--- | :---: | :--- |
| **Múltiples bodegas con existencias divididas** | **Diferido** | Se mantiene un único inventario central (`products.stock`) compartido entre web, WhatsApp y POS para máxima agilidad y cero discrepancias. |
| **CRM empresarial multi-agente** | **Diferido** | Se implementa la bitácora de interacciones directamente vinculada a cada pedido (`order_interactions`), suficiente para la atención requerida. |
| **Integración oficial Meta Cloud API** | **Diferido** | Se utilizan botones dinámicos `wa.me` con mensaje parametrizado, 100% funcionales sin costo ni trámites de verificación. |
| **Cobros reales PayPhone / Deuna** | **Diferido** | Se registran métodos y referencias en la estructura actual de pagos; la conexión a pasarelas reales queda aislada para fases futuras. |
| **Modificación destructiva del checkout web** | **Excluido** | El checkout existente para clientes web permanece intacto y 100% operativo. |
