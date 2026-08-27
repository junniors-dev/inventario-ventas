# Publicar una demostración en línea

Guía para dejar el sistema accesible desde una URL pública, de modo que
cualquiera pueda probarlo sin instalar nada.

## Por qué merece la pena

Un enlace donde alguien entra desde el móvil, escanea un producto y registra
una venta comunica más en treinta segundos que cualquier descripción del
repositorio.

---

## Opción recomendada: Railway

Detecta Laravel automáticamente y ofrece MySQL en el mismo proyecto. El plan de
prueba alcanza para una demostración; a partir de ahí ronda los 5 USD al mes.

### 1. Crear el proyecto

1. Entra en [railway.app](https://railway.app) y accede con tu cuenta de GitHub.
2. **New Project → Deploy from GitHub repo → `inventario-ventas`**.
3. En el mismo proyecto: **New → Database → Add MySQL**.

### 2. Configurar las variables

En la pestaña **Variables** del servicio web, añade:

```
APP_NAME=Inventario y Ventas
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-DOMINIO.up.railway.app

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=America/Lima

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

NEGOCIO_NOMBRE=Bodega Central
```

La sintaxis `${{MySQL.…}}` es de Railway: enlaza el servicio web con la base de
datos sin copiar credenciales a mano.

### 3. Generar la clave de la aplicación

`APP_KEY` cifra las sesiones y **no debe salir de tu repositorio**. Genera una
en tu equipo:

```bash
php artisan key:generate --show
```

Copia el resultado completo (empieza por `base64:`) y añádelo como variable
`APP_KEY` en Railway.

### 4. Cargar los datos de demostración

Una vez desplegado, desde la consola del servicio:

```bash
php artisan db:seed --force
```

Las migraciones ya se ejecutan solas en cada despliegue (`nixpacks.toml`).

### 5. Enlazar la demostración

Añade el dominio al principio del `README.md` y en **About** del repositorio:

```markdown
🔗 **[Ver demostración en vivo](https://TU-DOMINIO.up.railway.app)**

Entra con `admin@bodega.pe` / `password`.
```

---

## Alternativas

| Plataforma | Ventaja | Inconveniente |
| --- | --- | --- |
| **Render** | Capa gratuita real | La base MySQL no es gratuita; el servicio se duerme y tarda en despertar |
| **Fly.io** | `fly launch` reconoce Laravel | Configuración algo más manual |
| **VPS** (Hetzner, DigitalOcean) | Control total, desde 4 USD | Hay que instalar y mantener Nginx, PHP y MySQL |

---

## Antes de publicar, comprueba

- [ ] `APP_DEBUG=false` — con `true`, un error muestra el código fuente y las
      variables de entorno a cualquier visitante.
- [ ] `APP_KEY` configurada y distinta de la de desarrollo.
- [ ] Cambia las contraseñas de las cuentas de demostración si el enlace va a
      ser público, o asume que cualquiera puede entrar como administrador.
- [ ] `APP_URL` con el dominio real, para que los enlaces y los assets apunten
      donde deben.

## Nota sobre los datos de la demostración

Cualquiera que entre puede crear y anular ventas. Para que la demostración se
mantenga presentable, conviene reiniciarla de vez en cuando:

```bash
php artisan migrate:fresh --seed --force
```
