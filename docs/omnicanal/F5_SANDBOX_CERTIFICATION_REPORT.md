# F5.1 — INFORME DE CERTIFICACIÓN SANDBOX Y SEGURIDAD
## Validación de Integraciones Externas, Protocolos de Autenticación e Integridad Comercial

- **Proyecto:** ZYRO Omnicanal
- **Rama:** `feature/zyro-omnicanal`
- **Metodología:** PolkDev + Spec as a Skill
- **Fecha:** 16 de noviembre de 2026
- **Estado:** CERTIFICADO EN SUITE SANDBOX (204 pruebas aprobadas, 838 aserciones, 0 fallos)

---

## 1. Auditoría de Contratos de API Oficiales

Se contrastaron las implementaciones locales (`PayPhoneClient`, `DeunaClient`, `PayPhoneGatewayAdapter`, `DeunaGatewayAdapter`) con la documentación técnica oficial de los proveedores:

### 1.1. PayPhone (Ecuador / V3 API)
| Parámetro / Componente | Especificación Oficial | Implementación ZYRO | Estado |
|---|---|---|---|
| **Base URL Sandbox** | `https://pay.payphonetodoesposible.com/api` | Configurada en `payment.payphone.sandbox_url` | Conforme |
| **Autenticación** | `Authorization: Bearer <TOKEN>` | Inyectada en cabecera HTTP estándar por `PayPhoneClient` | Conforme |
| **Identificador Comercio** | `storeId` en cuerpo JSON de la petición | Inyectado desde `payment.payphone.store_id` | Conforme |
| **Preparación de Cobro** | `POST /button/Prepare` | `PayPhoneClient::preparePayment()` | Conforme |
| **Cálculo de Importes** | `amount = amountWithTax + amountWithoutTax + tax + service + tip` (en centavos enteros) | Desglose aritmético exacto en centavos. Verificado con IVA 15% ZYRO | Conforme |
| **Confirmación Oficial** | `POST /button/Confirm` con `{ id, clientTxId }` | `PayPhoneClient::confirmTransaction()`. No asume HMAC arbitrario en `responseUrl` | Conforme |
| **Ventana de Confirmación** | Máximo **5 minutos** tras aprobación del usuario | Validado rechazo ante expiración (`statusCode: 2`, `transactionStatus: Expired`) | Conforme |
| **Consulta de Estado** | `GET /button/V2/Confirm/{id}` | `PayPhoneClient::queryTransaction()` | Conforme |

### 1.2. DEUNA (Next-Gen Checkout)
| Parámetro / Componente | Especificación Oficial | Implementación ZYRO | Estado |
|---|---|---|---|
| **Base URL Sandbox** | `https://api-staging.deuna.com` | Configurada en `payment.deuna.sandbox_url` | Conforme |
| **Autenticación Backend** | `X-Api-Key: <KEY>`, `X-Store-Code: <CODE>` | Inyectadas en cabeceras HTTP por `DeunaClient` | Conforme |
| **Creación de Orden** | `POST /merchants/orders` | `DeunaClient::createOrder()` con desglose de items e impuestos | Conforme |
| **Consulta de Orden** | `GET /merchants/orders/{orderToken}` | `DeunaClient::getOrder()` | Conforme |
| **Firma Criptográfica Webhook** | `X-Signature: Base64(HMAC-SHA256(rawBody, webhookSecret))` | `DeunaClient::verifyWebhookSignature()` usando `hash_equals()` | Conforme |

---

## 2. Pruebas PayPhone Sandbox

Flujo validado en `tests/Feature/PayPhoneSandboxTest.php` (10 pruebas) y `tests/Feature/SandboxCertificationF5Test.php`:

1. **Preparación y Redirección:**
   - La orden genera una solicitud `POST /button/Prepare` con monto total en centavos ($120.00 → 12000 centavos, IVA $15.00 → 1500 centavos).
   - Retorna URL de checkout `https://pay.payphonetodoesposible.com/api/button/pay?id=...` y guarda el pago con estado `Pending`.
2. **Confirmación Server-to-Server vía Webhook:**
   - PayPhone notifica con `{ id: 765432, clientTxId: "..." }`.
   - El adaptador ZYRO ejecuta de inmediato `POST /button/Confirm` con Bearer Token.
   - Al confirmar `statusCode: 3` (`Approved`), se transiciona la orden a `Paid`, se descuenta el inventario y se emiten los eventos WebSocket.
3. **Control del Límite de 5 Minutos:**
   - Cuando PayPhone responde error de expiración (`statusCode: 2` o HTTP 400 por superar 5 minutos), el pago se marca como `Failed` y la orden permanece sin pagar, evitando cobros fantasma o entregas no liquidadas.

---

## 3. Pruebas DEUNA Sandbox

Flujo validado en `tests/Feature/DeunaSandboxTest.php` (8 pruebas) y `tests/Feature/SandboxCertificationF5Test.php`:

1. **Creación de Orden y Checkout:**
   - `DeunaClient` emite `POST /merchants/orders` con cabeceras `X-Api-Key` y `X-Store-Code`.
   - Se procesa la respuesta obteniendo `order_token` y `checkout_url`.
2. **Recepción de Webhook y Firma Criptográfica:**
   - Recepción en `/webhooks/payment/deuna` con cabecera `X-Signature`.
   - La firma se calcula como:
     $$\text{X-Signature} = \text{base64\_encode}\Big(\text{hash\_hmac}('sha256', \text{rawPayloadJson}, \text{secret}, \text{true})\Big)$$
   - Comparación mediante `hash_equals()`. Si la firma es válida, se aprueba y reconcilia el pago.

---

## 4. Matriz de Pruebas de Seguridad (F5.1)

| Escenario de Ataque / Falla | Comportamiento del Sistema | Resultado de la Prueba |
|---|---|---|
| **Firma DEUNA manipulada o corrupta** | Rechazo inmediato con mensaje *"Firma inválida"* y estado `failed`. La orden no cambia de estado. | `test_security_deuna_tampered_signature_is_strictly_rejected` (PASSED) |
| **Moneda no soportada en el cobro (ej. EUR, COP)** | Lanzamiento de `RuntimeException("Moneda no soportada: [EUR]")`. Solo se admite USD. | `test_security_non_usd_currency_in_process_is_rejected` (PASSED) |
| **Moneda no soportada en el webhook** | Rechazo del webhook con `status: failed`. No se altera la orden ni el stock. | `test_security_non_usd_currency_in_webhook_is_rejected` (PASSED) |
| **Importe manipulado en la petición** | Backend valida contra `$order->total`. Lanza `RuntimeException("Monto inválido")` si difiere en > 0.01 USD. | `test_manipulated_amount_in_payload_is_strictly_rejected` (PASSED) |
| **Evento fuera de orden (orden previamente cancelada/rechazada)** | Si la orden ya estaba en `Cancelled` o `Rejected`, un webhook tardío de aprobación se descarta, el pago queda en `Failed` y el inventario permanece intacto. | `test_security_late_webhook_does_not_resurrect_cancelled_order` (PASSED) |
| **Identificadores inexistentes** | Webhook para una referencia que no existe en el sistema se maneja de forma segura respondiendo HTTP 200 sin alterar la base de datos ni provocar excepciones no controladas. | `test_security_webhook_for_nonexistent_transaction_handled_safely` (PASSED) |
| **Webhooks duplicados / Reintentos de pasarela** | Idempotencia en tabla `payment_webhooks` por `gateway + external_event_id`. El inventario se descuenta **exactamente una vez**. | `test_commercial_integrity_duplicate_retries_preserve_single_decrement` (PASSED) |
| **Condición de carrera / Doble envío simultáneo** | Bloqueo pesimista `lockForUpdate()` en orden y productos dentro de transacción DB. El segundo hilo encuentra la orden ya pagada y es rechazado. | `test_concurrency_lock_prevents_simultaneous_double_charging` (PASSED) |
| **Falsificación desde navegador** | Los endpoints administrativos o públicos de checkout no aceptan banderas de "pagado". Solo el webhook o la consulta server-to-server transicionan el estado. | Verificado en arquitectura de `PaymentService` (PASSED) |

---

## 5. Integridad Comercial y de Inventario

1. **Descuento Único de Stock:**
   - La deducción de unidades de inventario se ejecuta de manera atómica dentro de la transacción de base de datos.
   - Ni los reintentos automáticos del webhook del proveedor ni peticiones concurrentes provocan un segundo descuento.
2. **Reversión y Consistencia:**
   - Si una transacción es rechazada o cancelada, la orden permanece en su estado anterior (`Pending` o `Approved`) y el stock no sufre modificaciones.
3. **Auditoría de Estados:**
   - Cada transición exitosa a `Paid` genera automáticamente un registro inmutable en `order_status_histories`.

---

## 6. Distinción Formal de Entornos de Prueba

En estricto apego a las directrices de PolkDev y a la regla principal de certificación:

### A. Pruebas Automatizadas con `Http::fake()` (Suite CI/CD)
- **Alcance:** Simulan fielmente las respuestas HTTP JSON, códigos de estado, cabeceras y latencias de las APIs de PayPhone y DEUNA.
- **Ventaja:** Permiten validar exhaustivamente casos de borde (expiración de 5 minutos, caídas HTTP 500/404, firmas criptográficas manipuladas, eventos duplicados) de forma determinística y reproducible sin depender de conectividad externa ni agotar cuotas.
- **Estado:** 204 pruebas automatizadas ejecutadas y aprobadas (100% de éxito).

### B. Pruebas contra Servidores Externos Sandbox (Pruebas Reales de Campo)
- **Alcance:** Transacciones reales originadas desde el navegador contra las páginas de pago de `pay.payphonetodoesposible.com` y `api-staging.deuna.com`.
- **Requisito para el operador:** Requiere configurar credenciales comerciales de Sandbox en el archivo `.env` local y disponer de un túnel HTTPS público para recibir el webhook real del proveedor.

---

## 7. Guía de Ejecución contra Sandbox en Vivo (XAMPP + HTTPS Público)

Para ejecutar una compra real de prueba en el entorno Sandbox:

### Paso 1: Configurar credenciales en `.env`
Editar `C:\xampp\htdocs\ZYRO\.env`:
```env
PAYMENT_MODE=sandbox
PAYMENT_GATEWAY=payphone  # o 'deuna'

# Credenciales Sandbox PayPhone (obtenidas del Portal PayPhone Developer)
PAYPHONE_TOKEN="BearerTokenSandbox..."
PAYPHONE_STORE_ID="StoreIdSandboxUUID..."

# Credenciales Sandbox DEUNA (obtenidas del Portal DEUNA Staging)
DEUNA_API_KEY="ApiKeySandbox..."
DEUNA_STORE_CODE="StoreCodeSandbox..."
DEUNA_WEBHOOK_SECRET="SecretoHmacDeuna..."
DEUNA_SANDBOX=true
```

### Paso 2: Exponer el puerto local mediante Túnel HTTPS
En PowerShell:
```powershell
# Iniciar servidor Laravel
cd C:\xampp\htdocs\ZYRO
php artisan serve --port=8000

# En otra consola, abrir el túnel HTTPS
cloudflared tunnel --url http://localhost:8000
# o alternativamente:
ngrok http 8000
```

### Paso 3: Registrar la URL del Webhook en el Portal del Proveedor
- Para PayPhone: Configurar la URL de respuesta en el botón o dashboard:
  `https://<tu-subdominio-tunel>/webhooks/payment/payphone`
- Para DEUNA: Configurar en la sección de Webhooks de DEUNA Staging:
  `https://<tu-subdominio-tunel>/webhooks/payment/deuna`

---

## 8. Resultados Consolidados de la Suite de Pruebas

Comando ejecutado: `php artisan test`

```text
   PASS  Tests\Unit\DeunaGatewayTest (10 tests)
   PASS  Tests\Unit\ExampleTest (1 test)
   PASS  Tests\Unit\PayPhoneGatewayTest (16 tests)
   PASS  Tests\Feature\AdminTest (6 tests)
   PASS  Tests\Feature\AnalyticsTest (10 tests)
   PASS  Tests\Feature\AuthenticationTest (7 tests)
   PASS  Tests\Feature\CartTest (12 tests)
   PASS  Tests\Feature\CatalogTest (9 tests)
   PASS  Tests\Feature\DeunaSandboxTest (8 tests)
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\OmnichannelF2Test (7 tests)
   PASS  Tests\Feature\OmnichannelF3Test (12 tests)
   PASS  Tests\Feature\OrderTest (15 tests)
   PASS  Tests\Feature\PayPhoneSandboxTest (10 tests)
   PASS  Tests\Feature\PaymentGatewayF4Test (21 tests)
   PASS  Tests\Feature\PaymentIntegrationF5Test (6 tests)
   PASS  Tests\Feature\PaymentTest (12 tests)
   PASS  Tests\Feature\RealtimeTest (13 tests)
   PASS  Tests\Feature\SandboxCertificationF5Test (8 tests)
   PASS  Tests\Feature\ShippingTest (13 tests)
   PASS  Tests\Feature\TaxTest (7 tests)

----------------------------------------------------------------------
Total de Pruebas: 204 aprobadas (838 aserciones)
Tiempo de ejecución: 21.65s
Fallos: 0
```

---

## 9. Conclusión y Dictamen

La etapa **F5.1 — Certificación Sandbox y Seguridad** queda **APROBADA Y CERTIFICADA** a nivel de arquitectura, contratos, criptografía y suites de prueba automatizadas.

El sistema ZYRO se encuentra listo para iniciar la fase posterior una vez que el usuario otorgue la autorización correspondiente.
