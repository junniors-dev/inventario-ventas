# Sistema de Inventario y Ventas

Aplicación web para que un negocio pequeño gestione su inventario, registre ventas
y consulte reportes. Construida con **Laravel 12**, **MySQL** y **Tailwind CSS**.

Es la reconstrucción de un CRUD que originalmente escribí en PHP plano con un MVC
hecho a mano, ahora aplicando las convenciones y herramientas del framework.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tests](https://github.com/TU-USUARIO/inventario-ventas/actions/workflows/tests.yml/badge.svg)
![Tests](https://img.shields.io/badge/tests-173%20passing-success)

---

## Funcionalidades

### Inventario
- CRUD de productos y categorías con validación en Form Requests.
- Búsqueda por nombre o código de barras, filtro por categoría y filtro de
  stock bajo.
- Código de barras opcional por producto, único e indexado.
- **Borrado lógico** (soft deletes): eliminar un producto lo oculta del catálogo
  pero conserva intacto el historial de ventas que lo referencia.
- Alertas visuales cuando el stock cae por debajo del mínimo configurado.

### Ventas
- Pantalla de venta tipo POS: buscador, filtros por categoría, carrito con
  cantidades y total calculado en vivo.
- **Lector de código de barras**: el buscador reconoce el disparo del lector
  (que se comporta como un teclado rápido seguido de Enter) y añade el producto
  al carrito al instante. Sin lector, el mismo campo funciona como
  autocompletado con navegación por teclado.
- Métodos de pago: efectivo, Yape, Plin y transferencia.
- Correlativo legible por comprobante (`VTA-2026-000147`).
- **Anulación de ventas** que reintegra el stock; la venta se conserva en el
  historial con su fecha de anulación en lugar de borrarse.
- Historial filtrable por comprobante, rango de fechas, vendedor, método de pago
  y estado, con el total del conjunto filtrado (no solo de la página visible).
- Datos de cliente opcionales por venta (nombre y DNI/RUC), buscables desde el
  historial y visibles en el comprobante.
- Exportación del historial filtrado a **CSV** listo para abrir en Excel.
- Descarga del comprobante en **PDF**, con el IGV desglosado.

### Reportes
- Dashboard con ventas del mes, variación frente al mes anterior y ticket
  promedio.
- Gráfico de ventas de los últimos 8 meses y ranking de productos más vendidos
  (Chart.js).
- Reparto de lo cobrado entre efectivo, Yape, Plin y transferencia.
- Ranking de vendedores del mes.
- Listado de productos por debajo del stock mínimo.

### Usuarios y permisos

Gestión de cuentas desde el panel: crear usuarios, asignar rol y cambiar
contraseñas. El sistema impide que un administrador se elimine a sí mismo o se
quite su propio rol, y bloquea el borrado de usuarios que ya tienen ventas
registradas para no romper el historial.

| Rol | Puede |
| --- | --- |
| **Administrador** | Todo: dashboard, productos, categorías, usuarios, ver y anular cualquier venta |
| **Vendedor** | Registrar ventas y consultar únicamente las suyas |

---

## Decisiones técnicas

Algunos puntos que van más allá de un CRUD básico:

**Control de concurrencia en el descuento de stock.** Registrar una venta ocurre
dentro de una transacción con bloqueo pesimista sobre las filas de los productos
implicados:

```php
DB::transaction(function () {
    $productos = Producto::whereIn('id', $ids)
        ->orderBy('id')      // orden estable: evita interbloqueos
        ->lockForUpdate()    // bloqueo pesimista
        ->get();
    // validar stock, crear el detalle y descontar
});
```

Sin esto, dos cajas vendiendo el mismo producto a la vez podrían leer el mismo
stock y venderlo dos veces. El bloqueo obliga a la segunda transacción a esperar
y releer el valor real.

**Dinero en `decimal`, nunca en `float`.** Precios y totales usan `decimal(10,2)`
en la base de datos y casts `decimal:2` en los modelos, para evitar los errores
de redondeo del punto flotante.

**El precio se congela en cada línea de venta.** `detalle_ventas` guarda el
`precio_unitario` del momento de la operación, así que cambiar el precio de un
producto no altera las ventas ya registradas.

**Autorización en dos niveles.** El middleware `role` protege secciones enteras
por rol; las policies deciden sobre registros concretos (por ejemplo, si *esta*
venta puede anularse y quién puede verla).

**El registro público está deshabilitado.** En un sistema de inventario no tiene
sentido que cualquiera se dé de alta y vea el stock del negocio: las cuentas las
crea un administrador desde el panel de usuarios.

**El precio de una venta nunca llega del cliente.** El punto de venta envía
únicamente identificadores y cantidades; el importe se recalcula en el servidor
con el precio vigente en la base de datos, de modo que manipular el formulario no
altera el total.

**Enums nativos de PHP** para roles, métodos de pago y estados de venta, casteados
directamente en los modelos.

**El lector de códigos no necesita drivers.** Un lector USB actúa como teclado:
teclea los dígitos en milisegundos y envía Enter. El punto de venta mide el
intervalo entre pulsaciones para distinguir un escaneo de la escritura humana, y
al recibir Enter busca primero una coincidencia exacta de código antes de caer en
la sugerencia resaltada del autocompletado.

**Detección de N+1 en desarrollo.** `Model::preventLazyLoading()` está activo
fuera de producción, de modo que una relación sin eager loading lanza una
excepción durante el desarrollo en lugar de degradar el rendimiento en silencio.

---

## Tecnologías

| Capa | Herramienta |
| --- | --- |
| Framework | Laravel 12 |
| Lenguaje | PHP 8.2+ |
| Base de datos | MySQL 8 |
| Autenticación | Laravel Breeze (stack Blade) |
| Vistas | Blade + Tailwind CSS |
| Interactividad | Alpine.js |
| Gráficos | Chart.js |
| PDF | barryvdh/laravel-dompdf |
| Tests | Pest |
| Build | Vite |

---

## Instalación

### Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8 (o MariaDB)
- Node.js 18+ y npm

### Pasos

```bash
git clone https://github.com/TU-USUARIO/inventario-ventas.git
cd inventario-ventas
```

Instala las dependencias:

```bash
composer install
npm install
```

Copia el archivo de entorno y genera la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

Crea la base de datos:

```bash
mysql -u root -e "CREATE DATABASE inventario_ventas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Ajusta las credenciales en `.env` si tu MySQL no usa el usuario `root` sin
contraseña:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventario_ventas
DB_USERNAME=root
DB_PASSWORD=
```

Ejecuta las migraciones junto con los datos de demostración:

```bash
php artisan migrate --seed
```

Compila los assets y levanta el servidor:

```bash
npm run build
php artisan serve
```

La aplicación queda disponible en `http://127.0.0.1:8000`.

> Durante el desarrollo puedes usar `npm run dev` en otra terminal para tener
> recarga automática de estilos y scripts.

### Cuentas de demostración

El seeder crea estas cuentas junto con 54 productos y unas 170 ventas repartidas
en los últimos 8 meses, de modo que el dashboard tenga datos desde el primer
arranque:

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@bodega.pe` | `password` |
| Vendedora | `maria@bodega.pe` | `password` |
| Vendedor | `carlos@bodega.pe` | `password` |

---

## Tests

```bash
php artisan test
```

La suite cubre 160 casos: control de acceso por rol, CRUD con sus validaciones,
descuento y reintegro de stock, correlativos de venta bajo colisión, códigos de
barras, gestión de usuarios, cálculo de métricas, exportación y generación del
PDF. Corre sobre SQLite en memoria, así que no toca la base de datos de
desarrollo.

Incluye una batería de 32 pruebas de seguridad dedicada:

| Vector | Qué se comprueba |
| --- | --- |
| Acceso sin sesión | Ninguna ruta interna responde a un invitado |
| Escalada de privilegios | Un vendedor no alcanza la administración ni se asciende desde su perfil |
| Referencias directas a objetos | Un vendedor no lee ni descarga el ticket de la venta de otro |
| Asignación masiva | Los campos no validados (`id`, `deleted_at`) se descartan |
| Manipulación del formulario | El precio y la cantidad enviados por el cliente no alteran el total |
| Inyección SQL | Los filtros con carga maliciosa no afectan al esquema |
| Cross-site scripting | El nombre de un producto se escapa al renderizarse |
| Credenciales | La contraseña se almacena con hash y no se serializa |
| Fuerza bruta | El login se bloquea tras cinco intentos fallidos |

Para ejecutar un archivo concreto:

```bash
php artisan test --filter=VentaTest
```

El estilo del código se verifica con Laravel Pint:

```bash
./vendor/bin/pint --test
```

Cada push a `main` y cada pull request ejecutan en GitHub Actions la
comprobación de estilo, la compilación de assets y la suite completa.

---

## Estructura del proyecto

```
app/
├── Actions/              Operaciones de negocio (registrar/anular venta, métricas)
├── Enums/                Role, MetodoPago, EstadoVenta
├── Exceptions/           StockInsuficienteException
├── Http/
│   ├── Controllers/
│   ├── Middleware/       EnsureUserHasRole
│   └── Requests/         Form Requests con las reglas de validación
├── Models/
└── Policies/             VentaPolicy
database/
├── factories/
├── migrations/
└── seeders/
resources/views/
├── components/           kpi-card, stock-badge, flash
├── categorias/
├── productos/
├── usuarios/
└── ventas/               incluye la vista del ticket PDF
tests/Feature/
```

La lógica de negocio vive en clases de acción en lugar de los controladores, que
se limitan a coordinar la petición HTTP, la autorización y la respuesta.

---

## Modelo de datos

```
categorias ──< productos ──< detalle_ventas >── ventas >── users
                (soft delete)
```

- Una categoría agrupa muchos productos.
- Una venta tiene muchas líneas de detalle; cada línea apunta a un producto y
  guarda la cantidad y el precio unitario del momento.
- Las claves foráneas usan `restrictOnDelete` hacia productos y usuarios para
  proteger el historial, y `cascadeOnDelete` entre venta y sus detalles.

---

## Posibles ampliaciones

- Buscador de productos con resultados en vivo en la pantalla de venta.
- Notificaciones por correo cuando un producto llega al stock mínimo.
- Módulo de compras a proveedores para registrar entradas de inventario.
- Exportación de reportes a Excel.
- API REST con Sanctum para una app móvil de caja.

---

## Licencia

MIT.
