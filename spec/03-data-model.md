# 03 — Modelo de datos

Reutilizar modelos/tablas existentes cuando corresponda.

## users
Tabla Laravel: `id`, `name`, `email` único, `password` hasheada, `role` (`customer`/`admin`) o mecanismo equivalente, timestamps. Registro público asigna siempre `customer`; crear admin mediante seeder/instrucción local.

## products
`id`, `name`, `slug` opcional único, `description`, `price DECIMAL(10,2)`, `image_path` nullable, `category` nullable, `size_options` JSON/text, `stock` unsigned, `is_active`, timestamps.

## orders
`id`, `order_number` único, `user_id`, snapshots de entrega (`customer_name`, `phone`, `address`, `city`, `delivery_method`, `delivery_notes`), `subtotal`, `tax_amount`, `shipping_amount`, `total` DECIMAL(10,2), `status`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `paid_at`, timestamps.
Estados principales: `pending_approval`, `approved`, `rejected`, `paid`, `cancelled`. Un pago fallido puede quedar registrado en `payments` sin cambiar el pedido de `approved`, permitiendo reintento.

## order_items
`id`, `order_id`, `product_id` nullable si procede, `product_name` snapshot, `size` nullable, `quantity` unsigned, `unit_price DECIMAL(10,2)`, `line_total DECIMAL(10,2)`, timestamps.

## payments
`id`, `order_id`, `method` (`test_card`, `bank_transfer_demo`, `wallet_demo`), `status` (`pending`, `approved`, `rejected`), `amount DECIMAL(10,2)`, `reference` ficticia única, `processed_at`, timestamps.
Prevenir pagos aprobados duplicados por pedido con restricción/validación apropiada.

## order_status_histories (opcional)
`id`, `order_id`, `changed_by`, `from_status`, `to_status`, `note`, timestamps.

## Integridad
Dinero en DECIMAL, no FLOAT. FK activas; cantidades positivas; stock no negativo; snapshots de nombre/precio. Ingresos = suma de pagos aprobados asociados a órdenes `paid`, no suma de pedidos pendientes. No borrar registros para simular reembolsos; reembolsos fuera de alcance.
