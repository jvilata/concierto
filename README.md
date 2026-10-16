# Concierto Solidario

Aplicación Quasar para validar, desde una cámara o introduciendo el código, la entrada y la consumición de un concierto benéfico. Cada QR tiene un acceso y un canje independientes.

## Frontend

Requiere Node.js 20 o superior.

```bash
npm install
npm run dev
```

La cámara necesita permiso del navegador y HTTPS, excepto en `localhost`. Para desarrollo, crea `.env` a partir de `.env.example` y apunta `VITE_API_URL` al backend PHP.

## Backend PHP

Requiere PHP 8.1+, PDO MySQL y MySQL/MariaDB. Ejecuta `backend/schema.sql` en la base de datos y copia `backend/config.example.php` como `backend/config.php`. Configura las variables de entorno `DB_DSN`, `DB_USER`, `DB_PASSWORD` y, si el frontend está en otro origen, `CORS_ORIGIN`.

Para probar localmente desde la carpeta del proyecto:

```bash
php -S 127.0.0.1:8000 -t backend
```

El endpoint queda disponible en `http://127.0.0.1:8000/api/validar.php`. Publica el frontend y la carpeta `backend/api` en el mismo dominio para producción, o configura `VITE_API_URL` y `CORS_ORIGIN` con sus URL reales. No publiques `config.php` en un repositorio.

## Formato del QR

El QR puede contener el código directamente, un JSON con `codigo`, `code`, `token` o `id`, o una URL con el parámetro `codigo`, `code` o `token`. El valor debe coincidir exactamente con `entradas.codigo_qr`.

El endpoint recibe `POST` JSON con `codigo` y `tipo` (`entrada` o `consumicion`). El resultado puede ser `success`, `repeated`, `missing` o `error`. La actualización solo afecta al campo correspondiente y solo cuando todavía está vacío.
