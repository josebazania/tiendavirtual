# Tienda virtual de cursos

Aplicación web en español para publicar y vender cursos. Incluye catálogo, cuentas de estudiantes, un aula con lecciones y un panel de administración. Está construida con Laravel 13 y PHP 8.3 o superior.

## Funcionalidades actuales

- Catálogo público con precios en soles peruanos (PEN) y detalle de cada curso publicado.
- Registro, inicio y cierre de sesión, y recuperación de contraseña por correo.
- Área **Mis cursos** y aula accesible para estudiantes inscritos. Las lecciones admiten texto y enlaces HTTPS de YouTube o Vimeo.
- Administración de cursos y lecciones: crear, editar, publicar, despublicar y eliminar. Los cursos con pedidos o inscripciones no se pueden eliminar; se pueden despublicar.
- Panel de configuración para título, logotipo, icono, descripción SEO, servidor SMTP y credenciales de Mercado Pago. Incluye envío de correo de prueba.
- Compra individual con Checkout Pro de Mercado Pago mediante Orders API. Las notificaciones verificadas permiten activar el acceso tras confirmar el pago.
- Diseño adaptable a móviles y tres cursos de inteligencia artificial para demostración mediante un seeder opcional.

## Requisitos

- PHP 8.3 o superior, con las extensiones requeridas por Laravel, Composer y el controlador de base de datos. Para las pruebas se necesita SQLite y sus extensiones PDO.
- [Composer](https://getcomposer.org/) y una base de datos MySQL o MariaDB para la configuración local descrita abajo.
- Laragon u otro servidor web configurado para servir el directorio `public` del proyecto.

Composer está configurado para resolver dependencias compatibles con PHP 8.3. Ejecuta `composer check-platform-reqs` para comprobar las extensiones de la instalación activa. La interfaz actual utiliza estilos incluidos en las vistas y no requiere compilar recursos para mostrarse.

## Instalación local

1. Clona el repositorio y entra en el directorio del proyecto.
2. Instala las dependencias y crea el archivo de entorno. En PowerShell:

   ```powershell
   composer install
   Copy-Item .env.example .env
   ```

   Si ya existe `.env`, conserva ese archivo y omite la copia.

3. Crea una base de datos vacía llamada `tiendavirtual` y configura la conexión en `.env` con las credenciales de tu entorno. Ejemplo para Laragon:

   ```dotenv
   APP_URL=http://tiendavirtual.test
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tiendavirtual
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Ajusta el usuario y la contraseña según tu instalación. El `.env.example` del proyecto usa SQLite por defecto, por lo que hay que cambiar la conexión para usar MySQL.

4. En una instalación nueva, genera la clave y ejecuta las migraciones:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```

   **Conserva y respalda `APP_KEY` junto con la base de datos.** La aplicación la utiliza para cifrar las credenciales guardadas. No la regeneres en una instalación existente sin planificar la migración de los datos cifrados.

5. Inicia el servidor y abre [http://tiendavirtual.test](http://tiendavirtual.test) si ese dominio está configurado en Laragon. Alternativamente, usa `php artisan serve` y ajusta `APP_URL` a la dirección utilizada.

`.env`, las bases SQLite, `vendor` y los archivos generados están excluidos de Git. Los datos de la base de datos local tampoco forman parte del repositorio.

## Primer administrador y cursos de demostración

Registra una cuenta desde `/registro` y, desde la raíz del proyecto, asígnale el rol de administrador:

```powershell
php artisan store:make-admin correo@ejemplo.com
```

Reemplaza el correo por el de la cuenta registrada. El panel se encuentra en `/admin` y su configuración en `/admin/configuracion`. No se incluyen credenciales administrativas en el repositorio.

Para cargar los tres cursos de demostración sobre inteligencia artificial:

```powershell
php artisan db:seed --class=AiDemoCoursesSeeder
```

El seeder omite los cursos de demostración cuyo título ya exista. Sus lecciones son ejemplos de texto; los videos reales se agregan desde el panel. Este comando es diferente de `php artisan db:seed` sin argumentos: el seeder predeterminado crea un usuario de ejemplo.

## Configuración del sitio y correo

En `/admin/configuracion`, el administrador puede establecer el título, logo, favicon y descripción SEO. También puede guardar el servidor, puerto, modo de seguridad, usuario, contraseña y remitente SMTP; después de guardar, puede enviar un correo de prueba desde la misma pantalla.

La contraseña SMTP y las credenciales de Mercado Pago se guardan cifradas en la base de datos, no en `.env`, y no se vuelven a mostrar en el formulario. Los campos secretos vacíos conservan los valores guardados; las casillas permiten borrarlos. La recuperación de contraseña requiere una configuración SMTP funcional.

## Mercado Pago

La integración actual utiliza **Checkout Pro con Orders API**, moneda PEN y una orden por curso. Configura en `/admin/configuracion` el **Access Token** y la **firma secreta del webhook** de la misma aplicación de Mercado Pago; la Public Key no se utiliza en el flujo actual.

En el [panel de desarrolladores de Mercado Pago](https://www.mercadopago.com.pe/developers/panel/app):

1. Selecciona la aplicación y abre **Configuración de Webhooks**.
2. Selecciona el entorno de prueba o producción correspondiente a tus credenciales.
3. Configura una URL pública HTTPS terminada en `/pagos/mercadopago/notificacion` y selecciona el evento **Order (Mercado Pago)**.
4. Guarda la configuración y copia la firma secreta al panel de la tienda. No compartas el token ni la firma.

Por ejemplo, si la aplicación está publicada y HTTPS está configurado para el dominio previsto, la URL sería:

```text
https://tiendavirtual.actibait.com/pagos/mercadopago/notificacion
```

El dominio debe apuntar al servidor donde corre la aplicación: `localhost` y `tiendavirtual.test` no reciben notificaciones externas por sí solos. En la implementación actual se requieren ambos secretos para iniciar el pago. El regreso del navegador no confirma la compra: el webhook valida su firma y consulta la orden a Mercado Pago antes de conceder acceso. Las notificaciones repetidas no duplican la inscripción.

La suite automatizada simula respuestas de Mercado Pago; todavía se debe realizar una compra completa en el entorno de prueba con credenciales y una URL accesible para verificar la integración de extremo a extremo. También queda pendiente una consulta manual del estado para pruebas locales sin webhook.

## Pruebas

```powershell
php artisan test
```

Las pruebas de funcionalidad utilizan SQLite en memoria según `phpunit.xml`. Cubren el catálogo, permisos de administración y aula, configuración cifrada, recuperación de contraseña y flujos de pago simulados. No envían pagos ni correos reales.

## Antes de publicar

- Configurar el dominio y HTTPS, el servidor web hacia `public`, y `APP_URL` con la dirección pública; desactivar la depuración en producción.
- Configurar y probar SMTP y la recuperación de contraseñas con el proveedor real.
- Configurar Mercado Pago y su webhook en el entorno adecuado, probar una compra completa y luego usar credenciales de producción cuando corresponda.
- Sustituir los cursos de demostración por contenido real y revisar los enlaces de video.
- Establecer copias de seguridad de la base de datos, archivos cargados y `APP_KEY`, y proteger el acceso administrativo.

## Licencia

El proyecto declara la licencia MIT en `composer.json`.
