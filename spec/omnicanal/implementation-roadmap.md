# Hoja de Ruta de Implementación — ZYRO Omnicanal (Alcance Refinado MVP)
**Metodología:** PolkDev | **Enfoque:** Spec as a Skill  
**Proyecto:** ZYRO — Comercio Electrónico Omnicanal  
**Fase Actual:** F1 — Alcance Refinado MVP (Completada y Lista)  
**Próxima Fase:** F2 — Estructuras, Migraciones Mínimas, Contratos y Pruebas  
**Estado:** Hoja de Ruta Ajustada para F2  

---

## 1. Matriz de Clasificación de Funcionalidades

| Módulo / Capacidad | Clasificación | Alcance Técnico en este MVP | Estado de Desarrollo |
| :--- | :---: | :--- | :---: |
| **Catálogo Web y Carrito** | `[EXISTENTE]` | Compra de autoservicio online sin modificaciones destructivas. | Operativo en Laravel 12 |
| **Mecanismo Transaccional de Stock** | `[EXISTENTE]` | `lockForUpdate()` y decremento directo en `products.stock`. | Operativo en Laravel 12 |
| **K-001: Registro de Ventas Manuales** | `[NUEVO - MVP]` | Formulario administrativo `/admin/ventas/crear` para ventas presenciales y por WhatsApp. | Diseñado en F1 $\rightarrow$ A construir en F2/F3 |
| **K-002: Gestión Unificada de Pedidos** | `[NUEVO - MVP]` | Listado consolidado en `/admin/pedidos` con filtros por canal, fecha y estado. | Diseñado en F1 $\rightarrow$ A construir en F2/F3 |
| **K-003: Botones wa.me y Bitácora** | `[NUEVO - MVP]` | Generador de enlaces `wa.me` dinámicos y tabla `order_interactions`. | Diseñado en F1 $\rightarrow$ A construir en F2/F3 |
| **K-004: Cambios y Devoluciones** | `[NUEVO - MVP]` | Autoservicio en `/mi-cuenta/pedidos` y bandeja `/admin/solicitudes` con inspección. | Diseñado en F1 $\rightarrow$ A construir en F2/F3 |
| **Múltiples Almacenes Físicos** | `[DIFERIDO]` | Excluido de este MVP; se utiliza el inventario centralizado de `products.stock`. | Futura ampliación |
| **Integración Oficial Meta Cloud API**| `[DIFERIDO]` | Excluido de este MVP; reemplazado por deep links nativos `wa.me`. | Futura ampliación |
| **Cobros Reales PayPhone / Deuna** | `[DIFERIDO]` | Excluido de este MVP; se registran formas de pago compatibles en `payments`. | Futura ampliación |
| **CRM Avanzado Multi-agente** | `[DIFERIDO]` | Excluido de este MVP; cubierto mediante `order_interactions` por pedido. | Futura ampliación |

---

## 2. Inventario de Migraciones Mínimas Necesarias para F2

Para este MVP únicamente se desarrollarán **4 migraciones no destructivas**:

1. **`2026_11_16_000001_add_channel_and_operator_to_orders_table.php`:**
   - Agrega a `orders`: `channel_origin` (default `'web'`), `created_by` (FK a `users`), `payment_method` y `payment_status`.
   - Índices compuestos para filtros rápidos por canal y estado.
2. **`2026_11_16_000002_create_order_interactions_table.php`:**
   - Crea `order_interactions` para la bitácora de atención omnicanal asociada a cada pedido.
3. **`2026_11_16_000003_create_return_requests_table.php`:**
   - Crea `return_requests` con estados (`recibida`, `en_revision`, `resuelta`, `rechazada`), peritaje de inspección (`inspected_condition`) y bandera de reincorporación a stock (`is_restocked`).
4. **`2026_11_16_000004_create_return_request_histories_table.php`:**
   - Crea `return_request_histories` para registrar cada transición de estado y comentarios del revisor.

---

## 3. Plan de Trabajo Delimitado para la Fase F2

La **Fase F2** autorizada se limitará estrictamente a los cimientos técnicos necesarios para este alcance reducido, sin implementar lógica externa ni alterar producción:

1. **Paso F2-01: Migraciones y Rollbacks Verificados:**
   - Creación de las 4 migraciones especificadas con métodos `up()` y `down()` estrictamente probados.
   - Verificación de reversión limpia sin pérdida de datos en `products` ni `orders`.
2. **Paso F2-02: Modelos Eloquent y Relaciones:**
   - Creación de modelos: `OrderInteraction`, `ReturnRequest`, `ReturnRequestHistory`.
   - Actualización de relaciones en [`Order`](file:///C:/xampp/htdocs/ZYRO/app/Models/Order.php), [`Product`](file:///C:/xampp/htdocs/ZYRO/app/Models/Product.php) y [`User`](file:///C:/xampp/htdocs/ZYRO/app/Models/User.php).
3. **Paso F2-03: Helpers y Contratos de Servicio:**
   - Helper de generación de URLs dinámicas para WhatsApp (`WhatsAppLinkHelper` o Blade Component).
   - Servicio base para registro manual de ventas (`ManualOrderService`).
   - Servicio de cambios y devoluciones con control de inventario (`ReturnRequestService`).
4. **Paso F2-04: Suite de Pruebas Automatizadas para el MVP:**
   - `ManualOrderTest`: Valida creación de venta manual, validación de stock, bloqueo pesimista y actualización inmediata en el catálogo.
   - `OrderFilterTest`: Valida filtros administrativos por canal (`web`, `whatsapp`, `punto_fisico`).
   - `WhatsAppLinkTest`: Valida que los enlaces dinámicos generen correctamente el texto codificado con el SKU/slug y número de orden.
   - `ReturnRequestTest`: Valida solicitud del cliente, inspección técnica y comprobación de que el stock NO se reintegra antes de la aprobación.
5. **Paso F2-05: Garantía de Regresión Cero:**
   - Ejecución de la suite previa de pruebas (`phpunit`) para constatar que [`OrderTest`](file:///C:/xampp/htdocs/ZYRO/tests/Feature/OrderTest.php), [`PaymentTest`](file:///C:/xampp/htdocs/ZYRO/tests/Feature/PaymentTest.php), [`CatalogTest`](file:///C:/xampp/htdocs/ZYRO/tests/Feature/CatalogTest.php) y [`ShippingTest`](file:///C:/xampp/htdocs/ZYRO/tests/Feature/ShippingTest.php) continúen aprobando al 100%.

---

## 4. Condiciones para Iniciar la Construcción de F2

- Conformidad formal del Product Owner respecto a esta especificación refinada y la matriz de alcance.
- Una vez validado este diagnóstico, se procederá con la creación controlada de las migraciones y estructuras de F2.
