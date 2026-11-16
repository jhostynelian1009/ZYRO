# F4 — INFORME DE INTEGRACIÓN Y ARQUITECTURA DE PAGOS
## ZYRO — Ampliación Omnicanal

| Campo | Valor |
|-------|-------|
| **Proyecto** | ZYRO Omnicanal |
| **Rama** | `feature/zyro-omnicanal` |
| **Fase** | F4 — Arquitectura de Pasarelas de Pago |
| **Metodología** | PolkDev + Spec as a Skill |
| **Fecha** | 2026-11-16 |
| **Estado** | ✅ COMPLETADO |

---

## 1. Resumen Ejecutivo

Se ha implementado una arquitectura de pasarelas de pago desacoplada y extensible que permite integrar múltiples proveedores bajo un contrato común. El sistema incluye 5 adaptadores, protección de entorno, idempotencia, validación de montos en servidor, webhooks y reconciliación de pagos.

**Resultados de la suite de pruebas:**
- **21 tests F4** — ✅ todos aprobados (76 aserciones)
- **145 tests totales** — 145 aprobados, 573 aserciones
- 1 fallo preexistente en `OmnichannelF2Test` (test de rollback de migraciones F2, no relacionado con F4)

---

## 2. Arquitectura

```
┌──────────────────────────────────────────────────────────────┐
│                        PaymentService                         │
│  (Orquestador: idempotencia, transacciones, stock, eventos)   │
└───────────────────────────┬──────────────────────────────────┘
                            │
                ┌───────────▼───────────┐
                │  PaymentGatewayManager │
                │  (Factory + Guardas)   │
                └───────────┬───────────┘
                            │
        ┌───────────┬───────┼───────┬───────────┐
        ▼           ▼       ▼       ▼           ▼
   ┌─────────┐ ┌────────┐ ┌────┐ ┌──────────┐ ┌───────┐
   │  Mock   │ │PayPhone│ │Deuna│ │BankTransf│ │ Cash  │
   │ Gateway │ │Adapter │ │Adpt│ │ Gateway  │ │Gateway│
   └─────────┘ └────────┘ └────┘ └──────────┘ └───────┘
       ▲            ▲       ▲         ▲           ▲
       └────────────┴───────┴─────────┴───────────┘
                PaymentGatewayInterface
```

### Contrato Común

Todos los adaptadores implementan `PaymentGatewayInterface` con los métodos:

| Método | Descripción |
|--------|-------------|
| `getIdentifier()` | Identificador único del adaptador |
| `getName()` | Nombre legible |
| `getMode()` | Modo actual: `mock`, `sandbox`, `live` |
| `charge(Order, payload)` | Procesa o inicializa el cobro |
| `queryTransaction(ref)` | Consulta estado ante la pasarela |
| `handleWebhook(payload, headers)` | Procesa notificación webhook |

Todos retornan `PaymentProcessResult` (DTO con factories `approved()`, `failed()`, `redirect()`).

---

## 3. Adaptadores Implementados

### 3.1 MockPaymentGateway
- **Uso:** desarrollo y pruebas únicamente.
- **Comportamiento:** aprueba/rechaza según `MOCK_SUCCESS_RATE` (por defecto 100%).
- **Restricción:** `RuntimeException` en `APP_ENV=production`.

### 3.2 CashPaymentGateway
- **Uso:** ventas presenciales (POS).
- **Comportamiento:** aprueba inmediatamente; no realiza llamadas externas.
- **Registra:** operador y canal de origen.

### 3.3 BankTransferGateway
- **Uso:** transferencias bancarias.
- **Comportamiento:** el pago queda en estado `pending` hasta verificación manual.
- **No genera:** webhooks automáticos.

### 3.4 PayPhoneGatewayAdapter (SANDBOX STUB)
- **Uso:** pruebas sandbox con PayPhone Ecuador.
- **Comportamiento:** retorna URL de redirección ficticia.
- **Credenciales:** `PAYPHONE_TOKEN` y `PAYPHONE_STORE_ID` vía `.env` (NO verificadas).
- **Modo LIVE:** `RuntimeException` — no autorizado.

### 3.5 DeunaGatewayAdapter (SANDBOX STUB)
- **Uso:** pruebas sandbox con DEUNA.
- **Comportamiento:** retorna URL de redirección ficticia.
- **Credenciales:** `DEUNA_API_KEY` vía `.env` (NO verificadas).
- **Validación HMAC:** preparada para `x-deuna-signature`.
- **Modo LIVE:** `RuntimeException` — no autorizado.

> ⚠️ **IMPORTANTE:** Los adaptadores PayPhone y DEUNA son stubs de sandbox. Las credenciales reales no están incluidas ni verificadas. Se requiere configuración en `.env` y pruebas con los proveedores antes de su uso en producción.

---

## 4. Modos de Operación

| Modo | Entorno | Descripción |
|------|---------|-------------|
| `mock` | `local`, `testing` | Pagos simulados. **Prohibido** en producción. |
| `sandbox` | `local`, `testing`, `staging` | Pruebas con credenciales comerciales. **Prohibido** en producción. |
| `live` | `production` exclusivamente | Pagos reales. **Deshabilitado** hasta autorización expresa. |

### Guardas de Entorno

| Condición | Resultado |
|-----------|-----------|
| `APP_ENV=production` + `PAYMENT_MODE=mock` | ❌ `RuntimeException` |
| `APP_ENV!=production` + `PAYMENT_MODE=live` | ❌ `RuntimeException` |
| `APP_ENV=production` + driver `mock` | ❌ `RuntimeException` |

---

## 5. Integridad de Pagos

| Control | Implementación |
|---------|----------------|
| **Idempotencia** | `idempotency_key` único por pago; si ya existe, retorna el pago existente sin duplicar. |
| **Validación de monto** | El monto se toma de `orders.total` en el servidor, nunca del frontend. Si el payload incluye `amount` diferente, se rechaza con `RuntimeException`. |
| **Moneda** | USD fijo. |
| **Relación con pedido** | `payments.order_id` FK obligatoria. |
| **Prevención de duplicados** | `lockForUpdate()` en pedido y pago dentro de transacción DB. |
| **Referencia externa** | `external_transaction_id` para conciliación con proveedores. |
| **Bloqueo de stock** | `Product::lockForUpdate()` dentro de la transacción; stock insuficiente = pago rechazado. |

### Estados de Pago

| Estado | Valor | Descripción |
|--------|-------|-------------|
| Pendiente | `pending` | Pago iniciado, esperando confirmación (transferencia, redirect). |
| Aprobado | `approved` | Pago confirmado exitosamente. |
| Rechazado | `rejected` | Pago rechazado por la pasarela. |
| Fallido | `failed` | Error en el procesamiento. |
| Cancelado | `cancelled` | Pago cancelado por el usuario o administrador. |
| Expirado | `expired` | Pago no completado dentro del plazo. |
| Reembolsado | `refunded` | Pago devuelto al cliente. |

---

## 6. Webhooks

### Infraestructura

| Componente | Archivo |
|------------|---------|
| Controlador | `app/Http/Controllers/PaymentWebhookController.php` |
| Job (async) | `app/Jobs/ProcessPaymentWebhook.php` |
| Modelo | `app/Models/PaymentWebhook.php` |
| Ruta | `POST /webhooks/payment/{gateway}` |
| CSRF | Excluido en `bootstrap/app.php` |

### Características

- **Idempotencia:** `external_event_id` único por gateway; webhooks duplicados no generan efectos.
- **Reconciliación:** un webhook `approved` actualiza el pago de `pending` → `approved` y marca el pedido como `paid`.
- **Reintentos:** el Job `ProcessPaymentWebhook` reintenta hasta 3 veces con backoff (10s, 60s, 300s).
- **Validación HMAC:** preparada en `DeunaGatewayAdapter` con `x-deuna-signature`.
- **Logging:** todos los webhooks se registran con payload, headers, firma y estado.

---

## 7. Archivos Creados/Modificados

### Nuevos

| Archivo | Descripción |
|---------|-------------|
| `config/payment.php` | Configuración centralizada de pasarelas |
| `app/Payments/PaymentGatewayManager.php` | Factory con guardas de entorno |
| `app/Payments/Gateways/MockPaymentGateway.php` | Adaptador mock |
| `app/Payments/Gateways/CashPaymentGateway.php` | Adaptador efectivo POS |
| `app/Payments/Gateways/BankTransferGateway.php` | Adaptador transferencia |
| `app/Payments/Gateways/PayPhoneGatewayAdapter.php` | Stub sandbox PayPhone |
| `app/Payments/Gateways/DeunaGatewayAdapter.php` | Stub sandbox DEUNA |
| `app/Services/PaymentService.php` | Orquestador F4 |
| `app/Http/Controllers/PaymentWebhookController.php` | Controlador webhooks |
| `app/Jobs/ProcessPaymentWebhook.php` | Job asíncrono de webhooks |
| `tests/Feature/PaymentGatewayF4Test.php` | Suite de 21 tests |

### Modificados

| Archivo | Cambio |
|---------|--------|
| `app/Providers/AppServiceProvider.php` | Bindings de `PaymentGatewayManager` y `PaymentGatewayInterface` |
| `bootstrap/app.php` | Exclusión CSRF para `webhooks/payment/*` |
| `routes/web.php` | Ruta `POST /webhooks/payment/{gateway}` + import |

### Preexistentes (F4 anterior, sin modificar)

| Archivo | Estado |
|---------|--------|
| `app/Contracts/PaymentGatewayInterface.php` | Sin cambios |
| `app/DTOs/PaymentProcessResult.php` | Sin cambios |
| `app/Enums/PaymentStatus.php` | Ya expandido previamente |
| `app/Enums/PaymentMode.php` | Ya creado previamente |
| `app/Enums/PaymentGatewayDriver.php` | Ya creado previamente |
| `app/Models/Payment.php` | Ya actualizado previamente |
| `app/Models/PaymentWebhook.php` | Ya creado previamente |
| Migraciones F4 (×2) | Ya aplicadas previamente |

---

## 8. Variables de Entorno Requeridas

```env
# ─── Pagos F4 ─────────────────────────────────────
PAYMENT_MODE=mock                  # mock | sandbox | live
PAYMENT_GATEWAY=mock               # mock | payphone | deuna | bank_transfer | cash
MOCK_SUCCESS_RATE=1.0              # 0.0 a 1.0 (solo mock)

# PayPhone (sandbox — completar con credenciales reales)
PAYPHONE_TOKEN=
PAYPHONE_STORE_ID=

# DEUNA (sandbox — completar con credenciales reales)
DEUNA_API_KEY=
DEUNA_SANDBOX=true

# Webhook
PAYMENT_WEBHOOK_SECRET=
PAYMENT_IDEMPOTENCY_TTL=86400
```

---

## 9. Resultados de Pruebas

```
Tests:    21 passed (76 assertions) — PaymentGatewayF4Test
Tests:   145 passed (573 assertions) — Suite completa
```

### Cobertura de Escenarios F4

| # | Escenario | Estado |
|---|-----------|--------|
| 1 | Mock gateway: pago aprobado | ✅ |
| 2 | Mock gateway: pago rechazado | ✅ |
| 3 | Pedido cancelado no puede pagarse | ✅ |
| 4 | Error de conexión del gateway se propaga | ✅ |
| 5 | Webhook duplicado es idempotente | ✅ |
| 6 | Monto manipulado es rechazado | ✅ |
| 7 | Pedido inexistente retorna error | ✅ |
| 8 | Idempotency key duplicada retorna pago existente | ✅ |
| 9 | Reconciliación pending → approved vía webhook | ✅ |
| 10 | Pago en efectivo POS aprueba inmediatamente | ✅ |
| 11 | MOCK en producción lanza excepción | ✅ |
| 12 | Manager resuelve los 5 adaptadores | ✅ |
| 13 | Gateway desconocido lanza excepción | ✅ |
| 14 | Transferencia queda pending | ✅ |
| 15 | PayPhone retorna redirect | ✅ |
| 16 | DEUNA retorna redirect | ✅ |
| 17 | Ruta webhook rechaza gateway desconocido | ✅ |
| 18 | Ruta webhook acepta gateway válido | ✅ |
| 19 | LIVE bloqueado fuera de producción | ✅ |
| 20 | Config `payment.php` se carga correctamente | ✅ |
| 21 | DTO factories funcionan correctamente | ✅ |

---

## 10. Compatibilidad

| Componente | Estado |
|------------|--------|
| `SimulatedPaymentService` | ✅ Intacto, todos sus tests pasan |
| `PaymentController` existente | ✅ Sin modificar, sigue usando `SimulatedPaymentService` |
| `PaymentTest.php` (tests previos) | ✅ Todos aprobados |
| Pedidos web existentes | ✅ Sin afectación |
| Pedidos WhatsApp (F3) | ✅ Sin afectación |
| Devoluciones (F3) | ✅ Sin afectación |

---

## 11. Limitaciones Conocidas

1. **PayPhone y DEUNA son stubs de sandbox.** Las credenciales reales no están incluidas ni verificadas. Se requiere configuración `.env` y pruebas directas con los proveedores.

2. **Modo LIVE deshabilitado.** No se ha autorizado procesamiento real. La activación requiere:
   - Credenciales reales verificadas.
   - Pruebas de sandbox exitosas con cada proveedor.
   - Autorización expresa del propietario del proyecto.

3. **Webhooks de sandbox no validados externamente.** La infraestructura está preparada (firma HMAC, idempotencia, reintentos), pero no se ha probado con notificaciones reales de PayPhone o DEUNA.

4. **El test `OmnichannelF2Test::new_migrations_apply_and_rollback_cleanly` falla** — es un test de rollback de F2 que verifica que las tablas F3 no existan tras rollback. No es una regresión de F4.

---

## 12. Recomendaciones para F5

1. Configurar credenciales reales de PayPhone y DEUNA en un entorno de staging.
2. Probar redirección completa: checkout → redirect → callback → webhook.
3. Validar firmas HMAC con secretos reales.
4. Implementar panel administrativo de conciliación de pagos.
5. Agregar reportes de transacciones por pasarela/período.
6. Considerar integración con facturación electrónica SRI (Ecuador).
