## Proyecto final de Backend

### Requisitos previos

Para poder correr el proyecto, se necesita tener instalado:

- PHP
- Composer
- Node.js
- MySQL

### Clonar el repositorio

```bash
git clone https://github.com/belsusaan/marketplace_productos_mascotas.git

cd marketplace_productos_mascotas
```

### Instalar dependencias

```bash
composer install

npm install
```

### Configurar .env

Se debe duplicar el archivo .env.example y renombrarlo a ".env"

```bash
cp .env.example .env
```

Dentro del archivo .env, configurar:

#### Base de datos

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketplace_productos_mascotas
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

#### Sanctum

```bash
APP_URL=http://marketplace_productos_mascotas.test
```

#### Mail

```bash
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="Marketplace de productos de mascotas"
```

### Genera la llave de la aplicación

```bash
php artisan key:generate
```

### Correr migraciones y seeders

Correr este comando para construir las tablas y poblarlas con los seeders.

```bash
php artisan migrate --seed
```

### Swagger

Correr el comando:

```bash
php artisan l5-swagger:generate
```

La documentación de los endpoints Swagger está disponible en el siguiente link:

[http://marketplace_productos_mascotas.test/api/documentation#/](http://marketplace_productos_mascotas.test/api/documentation#/)
