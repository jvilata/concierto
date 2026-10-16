<?php
declare(strict_types=1);

return [
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=concierto;charset=utf8mb4',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'cors_origin' => getenv('CORS_ORIGIN') ?: '*',
];