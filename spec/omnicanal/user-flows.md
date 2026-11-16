# Flujos de Usuario e Interacción — ZYRO Omnicanal (Alcance Refinado MVP)
**Metodología:** PolkDev | **Enfoque:** Spec as a Skill  
**Proyecto:** ZYRO — Comercio Electrónico Omnicanal  
**Fase:** F1 — Especificación de Flujos Ajustada para F2  
**Estado:** Documento de Flujos Consolidado  

---

## 1. Recorrido 1: Registro de Ventas Manuales por Canal (K-001)

Ilustra la creación de una venta directa (presencial en Guayaquil o asistida por WhatsApp) desde el panel administrativo, reutilizando el inventario central con bloqueo transaccional.

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Operador / Asesor Administrativo
    participant Panel as Interfaz /admin/ventas/crear
    participant Ctrl as OrderController (Admin)
    participant DB as MySQL (InnoDB lockForUpdate)
    participant Reverb as Reverb WebSockets
    participant Web as Tienda Web Pública

    Admin->>Panel: Accede a «Registrar venta»
    Admin->>Panel: Selecciona cliente existente o ingresa datos de nuevo cliente
    Admin->>Panel: Selecciona producto (Chompa Negra), talla (M) y cantidad (1)
    Admin->>Panel: Selecciona Canal de Origen (Punto Físico o WhatsApp)
    Admin->>Panel: Selecciona Método de Pago (Efectivo / Transferencia / Tarjeta)
    Admin->>Panel: Selecciona Modalidad de Entrega (Mostrador / Envío)
    Admin->>Panel: Clic en «Confirmar y Registrar Venta»
    Panel->>Ctrl: POST /admin/ventas (payload de la venta)
    Ctrl->>DB: Inicia DB::transaction
    Ctrl->>DB: SELECT * FROM products WHERE id=X FOR UPDATE
    DB-->>Ctrl: Fila bloqueada pesimistamente (Stock actual: 5)
    Ctrl->>Ctrl: Valida stock >= 1
    Ctrl->>DB: UPDATE products SET stock = stock - 1 WHERE id=X
    Ctrl->>DB: INSERT INTO orders (order_number: ZYR-501, channel_origin: punto_fisico, created_by: AdminID, status: paid)
    Ctrl->>DB: INSERT INTO order_items (...)
    Ctrl->>DB: Commit de transacción
    Ctrl->>Reverb: Emite evento InventoryUpdated (Stock = 4)
    Reverb-->>Web: Catálogo web público actualiza el saldo disponible al instante
    Ctrl-->>Panel: Redirecciona al detalle de la orden con mensaje de éxito
```

---

## 2. Recorrido 2: Gestión Unificada y Continuación de Compra Multicanal (K-002)

Ilustra la continuidad de una venta asistida iniciada en WhatsApp que el cliente culmina pagando o retirando en el Punto Físico de Guayaquil, manteniendo el mismo número de pedido sin duplicarlo.

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente
    actor Asesor as Asesor WhatsApp
    actor Cajero as Cajero Punto Físico
    participant Panel as Panel Administrativo de Pedidos
    participant DB as Base de Datos MySQL

    C->>Asesor: Solicita compra asistida por WhatsApp
    Asesor->>Panel: Registra pedido en /admin/ventas con canal 'whatsapp' y estado 'pending_approval'
    Panel->>DB: Genera orden ZYR-6020 y descuenta stock
    Asesor-->>C: "Tu pedido ZYR-6020 está reservado. Puedes pagarlo y retirarlo en nuestro local de Guayaquil."
    Note over C,Cajero: Cliente acude al Punto Físico en Guayaquil
    C->>Cajero: "Vengo a retirar mi pedido ZYR-6020"
    Cajero->>Panel: Accede a /admin/pedidos y busca "ZYR-6020"
    Panel->>DB: Consulta la orden ZYR-6020 existente
    Panel-->>Cajero: Muestra detalle del pedido, prendas y saldo pendiente
    Cajero->>C: Recibe el pago en efectivo o comprobante
    Cajero->>Panel: Marca orden ZYR-6020 como 'paid' y entrega producto
    Panel->>DB: UPDATE orders SET status='paid', paid_at=NOW() WHERE id=6020
    Note over Panel,DB: No se crea ningún pedido nuevo; se preserva ZYR-6020 íntegro
    Panel-->>Cajero: Confirma actualización exitosa
```

---

## 3. Recorrido 3: Comunicación Omnicanal con Enlaces Dinámicos y Bitácora (K-003)

Ilustra la asistencia mediante enlaces parametrizados de WhatsApp en producto y pedido, y el registro de la interacción en la bitácora del pedido.

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente
    participant VistaWeb as Tienda Web / Mi Cuenta
    participant WA as Aplicación WhatsApp Business
    actor Asesor as Asesor ZYRO
    participant AdminOrder as Vista /admin/pedidos/{order}
    participant DB as Tabla order_interactions

    alt Caso A: Consulta de Producto
        C->>VistaWeb: Clic en botón "Consultar por WhatsApp" en Chompa Tech M
        VistaWeb->>WA: Abre chat con texto: "Hola, quiero consultar por la chompa Chompa Tech talla M. Referencia: chompa-tech"
    else Caso B: Consulta de Pedido
        C->>VistaWeb: Clic en "Consultar mi pedido por WhatsApp" en /mi-cuenta/pedidos/ZYR-1005
        VistaWeb->>WA: Abre chat con texto: "Hola, quisiera consultar sobre mi pedido ZYR-1005"
    end
    WA->>Asesor: Asesor recibe mensaje con contexto claro
    Asesor->>C: Asesor atiende y aclara dudas del cliente
    Asesor->>AdminOrder: Ingresa a /admin/pedidos/ZYR-1005 en sección "Historial de Atención"
    Asesor->>AdminOrder: Registra: Canal: WhatsApp | Tipo: Coordinación de entrega | Notas: "Cliente confirmó retiro el jueves"
    AdminOrder->>DB: INSERT INTO order_interactions (...)
    AdminOrder-->>Asesor: Muestra nota registrada en la línea de tiempo del pedido
```

---

## 4. Recorrido 4: Solicitud de Cambio/Devolución e Inspección Administrativa (K-004)

Ilustra el autoservicio del cliente para solicitar cambio o devolución conforme al Art. 45 de la LODC Ecuador, la inspección técnica en el punto físico y el control estricto de reincorporación al inventario.

```mermaid
sequenceDiagram
    autonumber
    actor C as Cliente
    participant Cuenta as Cuenta Cliente (/mi-cuenta/pedidos)
    actor Admin as Operador / Revisor de Calidad
    participant AdminCare as Módulo /admin/solicitudes
    participant DB as MySQL (products & return_requests)

    C->>Cuenta: Clic en «Solicitar cambio o devolución»
    C->>Cuenta: Selecciona orden ZYR-1005, prenda Chompa Azul M y motivo "Cambio de talla por L"
    C->>Cuenta: Envía solicitud
    Cuenta->>DB: INSERT INTO return_requests (request_number: DEV-001, status: 'recibida', inspected_condition: 'pendiente')
    Cuenta-->>C: Notifica código DEV-001 y pasos para entregar la prenda en local
    Note over C,Admin: Cliente entrega físicamente la prenda en el Punto Físico
    Admin->>AdminCare: Localiza solicitud DEV-001 y cambia estado a 'en_revision'
    Admin->>Admin: Realiza examen físico de la prenda
    alt Dictamen Favorable: Prenda Nueva Intacta
        Admin->>AdminCare: Registra: inspected_condition: 'optimo_con_etiquetas', decision: 'Aprobado cambio por L'
        Admin->>AdminCare: Marca reincorporación de prenda devuelta (is_restocked = true)
        AdminCare->>DB: UPDATE products SET stock = stock + 1 WHERE id=Chompa_M (Reingreso seguro)
        AdminCare->>DB: UPDATE products SET stock = stock - 1 WHERE id=Chompa_L (Salida reemplazo)
        AdminCare->>DB: UPDATE return_requests SET status = 'resuelta', resolved_at = NOW()
        Admin->>C: Entrega la nueva talla L al cliente
    else Dictamen Desfavorable: Prenda Dañada por Mal Uso
        Admin->>AdminCare: Registra: inspected_condition: 'no_apto', decision: 'Rechazado por rotura visible'
        AdminCare->>DB: UPDATE return_requests SET status = 'rechazada'
        Note over DB: El stock vendible NO se modifica en absoluto
        Admin-->>C: Notifica resolución motivada con base legal
    end
```
