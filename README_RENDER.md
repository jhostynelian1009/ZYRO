# Guía de Despliegue Temporal de ZYRO en Render

Este documento detalla el procedimiento para desplegar una instancia temporal del e-commerce **ZYRO** en **Render** utilizando Docker, de forma completamente aislada para no afectar la versión en producción que opera en **Railway**.

---

## 1. Aislamiento y Control de Versiones

- **Rama exclusiva para Render:** `deploy/render-temporary`.
- **Rama de producción en Railway:** `Develop` / `main`.
- **Regla estricta:** No realizar merge automático de `deploy/render-temporary` hacia `Develop` ni hacia `main`.
- **Credenciales:** Ningún archivo `.env` o credencial privada está incluido en el repositorio.

---

## 2. Configuración del Servicio en Render

1. Inicia sesión en [Render Dashboard](https://dashboard.render.com/).
2. Haz clic en **New +** y selecciona **Web Service**.
3. Conecta el repositorio: `https://github.com/jhostynelian1009/ZYRO.git`.
4. En la configuración del servicio, establece:
   - **Name:** `zyro-temporary` (o el nombre deseado).
   - **Branch:** `deploy/render-temporary`.
   - **Region:** Selecciona la región más cercana a la base de datos de Railway (ej. `US East (Ohio)`).
   - **Language / Runtime:** **Docker**.
   - **Dockerfile Path:** `./Dockerfile`.
   - **Docker Context Directory:** `.`.
   - **Instance Type:** `Free` (o `Starter`).
   - **Health Check Path:** `/up`.

---

## 3. Variables de Entorno en Render

En la pestaña **Environment** de tu servicio web en Render, configura las siguientes variables:

### A. Aplicación y Entorno
| Variable | Valor Recomendado | Descripción |
| :--- | :--- | :--- |
| `APP_NAME` | `ZYRO` | Nombre de la aplicación. |
| `APP_ENV` | `production` | Entorno de producción. |
| `APP_KEY` | `base64:...` | Clave de cifrado de Laravel (puedes generar una con `php artisan key:generate --show`). |
| `APP_DEBUG` | `false` | Desactivar depuración para seguridad en producción. |
| `APP_URL` | `https://tu-servicio.onrender.com` | URL pública asignada por Render. |

### B. Base de Datos (Conexión a MySQL en Railway)
> [!IMPORTANT]
> **No utilizar `mysql.railway.internal`:**
> El host interno de Railway (`mysql.railway.internal`) **solo** es accesible dentro de la red privada de Railway. Render es un proveedor externo, por lo que debe conectarse a través del **TCP Proxy público** proporcionado por Railway.

Para obtener las credenciales públicas de MySQL en Railway:
1. Abre tu proyecto en Railway y selecciona el servicio **MySQL**.
2. Ve a la pestaña **Settings** > **Networking** > **Public Networking**.
3. Habilita o copia el dominio público asignado (ej. `roundhouse.proxy.rlwy.net`) y el puerto asignado (un número de 5 dígitos, ej. `42183`, no el estándar `3306`).
4. En la pestaña **Variables** de Railway, consulta el usuario (`MYSQLUSER` o `root`) y la contraseña (`MYSQLPASSWORD` o `MYSQL_ROOT_PASSWORD`).

Configura en Render:
| Variable | Ejemplo de Valor | Descripción |
| :--- | :--- | :--- |
| `DB_CONNECTION` | `mysql` | Driver de base de datos MySQL. |
| `DB_HOST` | `roundhouse.proxy.rlwy.net` | Host público / TCP Proxy de Railway (NO usar `mysql.railway.internal`). |
| `DB_PORT` | `42183` | Puerto público asignado por Railway. |
| `DB_DATABASE` | `railway` | Nombre de la base de datos en Railway. |
| `DB_USERNAME` | `root` | Usuario de MySQL en Railway. |
| `DB_PASSWORD` | `tu_contraseña_railway` | Contraseña de MySQL en Railway. |

### C. Sesiones, Caché y Almacenamiento
| Variable | Valor | Descripción |
| :--- | :--- | :--- |
| `SESSION_DRIVER` | `database` | Almacena sesiones en la tabla `sessions` de MySQL para compartirlas sin depender del disco local. |
| `SESSION_LIFETIME` | `120` | Minutos de expiración de sesión. |
| `SESSION_SECURE_COOKIE`| `true` | Exigir HTTPS para cookies de sesión. |
| `CACHE_STORE` | `database` (o `file`) | Almacén de caché. |
| `QUEUE_CONNECTION` | `sync` (o `database`)| Procesamiento sincrónico de colas en entorno simple. |
| `FILESYSTEM_DISK` | `local` | Disco predeterminado. |

### D. Reglas Comerciales de ZYRO
| Variable | Valor | Descripción |
| :--- | :--- | :--- |
| `IVA_RATE` | `0.15` | Tasa oficial del 15% de IVA. |
| `SHIPPING_COST`| `6.00` | Tarifa oficial de envío de $6.00 USD. |

### E. Analítica y Servicios Opcionales
| Variable | Valor | Descripción |
| :--- | :--- | :--- |
| `CLARITY_PROJECT_ID` | `tu_id_clarity` | Identificador de Microsoft Clarity (opcional). |

---

## 4. Ejecución de Migraciones

> [!CAUTION]
> **Nunca ejecutar `migrate:fresh` en producción.**
> La base de datos contiene los productos, usuarios y pedidos existentes.

El contenedor de Docker está configurado para **no ejecutar migraciones automáticamente durante el build ni en cada inicio**.

### Opción A: Ejecución Manual Segura desde la Shell de Render (Recomendada)
1. En el Dashboard de Render, abre tu servicio web y ve a la pestaña **Shell**.
2. Verifica la conectividad con la base de datos ejecutando:
   ```bash
   php artisan db:monitor
   ```
3. Ejecuta las migraciones pendientes sin riesgo de pérdida de datos:
   ```bash
   php artisan migrate --force
   ```

### Opción B: Activación Automática Temporal vía Variable de Entorno
Si prefieres que se ejecuten al iniciar el contenedor:
1. Agrega temporalmente la variable en Render:
   `RUN_MIGRATIONS=true`
2. El script `docker/entrypoint.sh` detectará la variable y ejecutará `php artisan migrate --force`.
3. Una vez finalizado el despliegue, puedes retirar la variable.

---

## 5. Almacenamiento de Archivos y Personalizaciones

### Comportamiento en Render Free
En la capa gratuita de Render, el sistema de archivos del contenedor es **efímero**. Esto significa que:
- Las imágenes de productos provistas en el repositorio (`public/images/`) están incluidas en la imagen de Docker y persisten sin problema.
- Los archivos temporales subidos por clientes al personalizar chompas (`storage/app/private/customizations/temp`) se borrarán si el contenedor entra en suspensión o se reinicia.

### Opciones para Persistencia a Largo Plazo
1. **Render Disks (Planes de Pago):**
   Render permite asociar un volumen persistente montado en `/var/www/html/storage/app`. Requiere una instancia de pago (*Starter* o superior).
2. **Almacenamiento en la Nube (S3 / Cloudflare R2):**
   Configurar el disco `s3` de Laravel configurando las variables:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=tu_key
   AWS_SECRET_ACCESS_KEY=tu_secret
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=tu-bucket
   AWS_ENDPOINT=https://tu-cuenta.r2.cloudflarestorage.com
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```

---

## 6. Tiempo Real (WebSockets y Polling)

- **Laravel Reverb:**
  Render en capa gratuita solo expone el puerto HTTP asignado (`$PORT`) para Nginx. Por tanto, el servidor WebSocket de Reverb en puerto separado no estará activo por defecto a menos que se configure un servicio secundario (*Background Worker* o *Web Service* adicional).
- **Mecanismo de Respaldo Automático:**
  La aplicación ZYRO incluye un servicio de emisión segura (`SafeBroadcaster`) que previene caídas si Reverb no responde, y el panel administrativo cuenta con un endpoint nativo de sondeo recurrente:
  `/admin/actualizaciones`
  Este mecanismo asegura que clientes y administradores sigan recibiendo actualizaciones de pedidos, cambios de estado y métricas incluso en entornos sin WebSockets activos.

---

## 7. Verificación del Despliegue

Una vez completado el despliegue en Render:

1. **Ruta de Salud (Health Check):**
   Visita `https://tu-servicio.onrender.com/up` para confirmar que Nginx y PHP-FPM responden con código HTTP 200.
2. **Catálogo y Tienda Pública:**
   Visita la página principal y navega por el catálogo de chompas.
3. **Flujo de Compra y Logística:**
   - Agrega una chompa al carrito.
   - Ve al checkout y selecciona entre **Envío estándar ($6.00)**, **Envío exprés ($6.00)** y **Retiro en tienda (GRATIS)**.
   - Confirma el pedido y completa el pago simulado.
4. **Panel Administrativo:**
   Inicia sesión con la cuenta de administrador en `/login` y revisa `/admin/pedidos` y `/admin/inventario`.
