# Catálogo de Reglas de Negocio — ZYRO Omnicanal (Alcance Refinado MVP)
**Metodología:** PolkDev | **Enfoque:** Spec as a Skill  
**Proyecto:** ZYRO — Comercio Electrónico Omnicanal  
**Fase:** F1 — Reglas de Negocio Refinadas para F2  
**Estado:** Documento de Reglas Consolidado  

---

## 1. Reglas de Control de Inventario y Ventas Multicanal (K-001)

### RN-INV-01: Fuente Única de Verdad de Inventario
1. Todas las compras realizadas en la plataforma (tienda web pública, venta asistida por WhatsApp o venta presencial en punto físico) consultan y descuentan directamente sobre la columna `products.stock`.
2. **Prohibición Expresa:** Queda terminantemente prohibido implementar un segundo sistema o tabla paralela de inventario en este MVP.

### RN-INV-02: Transaccionalidad Atómica y Prevención de Sobreventa
1. Todo registro de venta en `/admin/ventas` debe ejecutarse dentro de un bloque `DB::transaction`.
2. Se debe bloquear pesimistamente la fila de cada producto solicitado mediante `lockForUpdate()`:
   ```php
   $product = Product::where('id', $id)->lockForUpdate()->first();
   ```
3. Si `stock < cantidad_solicitada`, la transacción aborta arrojando un error controlado sin alterar la base de datos.
4. Se prohíben cantidades negativas o nulas ($qty \ge 1$).

### RN-INV-03: Reflejo Inmediato y Difusión en Tiempo Real
1. Al confirmarse la venta manual, el decremento en `products.stock` se consolida de inmediato.
2. La nueva disponibilidad se refleja de manera instantánea en el catálogo público web y emite el evento WebSocket `SafeBroadcaster::broadcast(new InventoryUpdated(...))` para actualizar los navegadores activos.

---

## 2. Reglas de Gestión Unificada de Pedidos (K-002)

### RN-ORD-01: Identificador Único Universal
1. Cada pedido generado (web, WhatsApp o punto físico) recibe un número de pedido oficial y correlativo (`ZYR-XXXX`).
2. Dicho número es el código de referencia único que identifica la compra en todos los canales.

### RN-ORD-02: Continuidad de Compra sin Duplicación
1. Si un cliente inicia una compra asistida por WhatsApp y acuerda pagar o retirar en el Punto Físico de Guayaquil:
   - El asesor crea el pedido con canal `whatsapp` en estado `pending_approval` o `pending_payment`.
   - Cuando el cliente llega al local físico, el cajero consulta el pedido existente mediante su número (`ZYR-XXXX`).
   - El cajero registra el cobro o la entrega sobre el **mismo pedido**.
   - **Regla Estricta:** No se permite crear un nuevo pedido durante la continuación presencial de una compra ya iniciada.

### RN-ORD-03: Trazabilidad de Responsables
1. Toda venta registrada a través del panel administrativo debe almacenar el ID del usuario autenticado en la columna `orders.created_by`.
2. Las compras web de autoservicio conservan `created_by = null` y `channel_origin = 'web'`.

---

## 3. Reglas de Comunicación Asistida (K-003)

### RN-COM-01: Generación Dinámica de Enlaces de WhatsApp
1. Los botones de WhatsApp deben estructurar la URL dinámica hacia el número oficial configurado en `config/shop.php` (`WHATSAPP_NUMBER`):
   - **En Ficha de Producto:** Debe contener el nombre de la prenda, la talla seleccionada y el slug/código:  
     `https://wa.me/{phone}?text=Hola,%20quiero%20consultar%20por%20la%20chompa%20{nombre}%20talla%20{talla}.%20Referencia:%20{slug}`
   - **En Pedido del Cliente:** Debe citar obligatoriamente el número de orden:  
     `https://wa.me/{phone}?text=Hola,%20quisiera%20consultar%20sobre%20mi%20pedido%20{order_number}`
2. **Transparencia Técnica:** La funcionalidad opera mediante deep links universales `wa.me`. No se afirma ni se asume integración con Meta Cloud API para esta entrega.

### RN-COM-02: Bitácora de Atención y Seguimiento
1. Todo contacto relevante sostenido con el cliente referente a un pedido (llamada, WhatsApp, visita al local) puede registrarse en `order_interactions`.
2. Se debe almacenar la fecha, el canal utilizado, el usuario responsable, el tipo de contacto y las notas de seguimiento.

---

## 4. Reglas de Cambios, Devoluciones y Protección al Consumidor (K-004)

### RN-CARE-01: Marco Legal y Plazos (Art. 45 LODC Ecuador)
1. Conforme a la legislación vigente de defensa del consumidor en Ecuador, el cliente dispone de **15 días hábiles** a partir de la recepción efectiva del pedido para ingresar solicitudes de cambio o retracto.
2. **Garantía Legal por Defectos:** Las fallas de costura, telas defectuosas o errores atribuibles a ZYRO son derechos irrenunciables que **no se limitan** al plazo de 15 días de cambios por preferencia.

### RN-CARE-02: Aislamiento Estricto y Prohibición de Reingreso Automático al Stock
1. Toda prenda devuelta por un cliente se registra en `return_requests` con condición `inspected_condition = 'pendiente'`.
2. **Regla de Oro:** La prenda **jamás se suma automáticamente al stock vendible** (`products.stock`).
3. El reingreso al stock vendible solo puede ocurrir si se cumplen simultáneamente tres condiciones:
   - Inspección física completada por el personal con dictamen `optimo_con_etiquetas`.
   - Decisión administrativa que autoriza explícitamente el reingreso (`is_restocked = true`).
   - Ejecución mediante transacción atómica que incrementa `products.stock`.

### RN-CARE-03: Compensación en Cambios de Prenda
1. Si la solicitud aprobada consiste en un cambio por otra talla o producto:
   - La prenda entregada en reemplazo se descuenta inmediatamente de `products.stock` mediante `decrement('stock', 1)`.
   - Se registra el producto sustituto en `replacement_product_id` y `replacement_size`.

### RN-CARE-04: Transparencia en Reembolsos
1. Si procede la devolución de dinero, se reintegrará el valor al medio de pago original o cuenta bancaria del titular.
2. Queda estrictamente prohibido obligar al cliente a recibir notas de crédito o saldo de tienda en contra de su voluntad.
