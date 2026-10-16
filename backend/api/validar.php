<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require dirname(__DIR__) . '/config.php';
$allowedOrigin = $config['cors_origin'] ?? '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['estado' => 'error', 'mensaje' => 'Método no permitido.']);
}

$request = json_decode(file_get_contents('php://input'), true);
$code = is_array($request) ? trim((string) ($request['codigo'] ?? '')) : '';
$type = is_array($request) ? ($request['tipo'] ?? '') : '';

if ($code === '' || strlen($code) > 255 || !in_array($type, ['entrada', 'consumicion'], true)) {
    respond(400, ['estado' => 'error', 'mensaje' => 'Código o tipo de validación no válido.']);
}

$column = $type === 'entrada' ? 'entrada_consumida_at' : 'consumicion_consumida_at';
$itemName = $type === 'entrada' ? 'La entrada' : 'La consumición';

try {
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $update = $pdo->prepare("UPDATE entradas SET {$column} = CURRENT_TIMESTAMP WHERE codigo_qr = :codigo AND {$column} IS NULL");
    $update->execute(['codigo' => $code]);

    if ($update->rowCount() === 1) {
        respond(200, [
            'estado' => 'success',
            'mensaje' => $type === 'entrada' ? 'Entrada válida' : 'Consumición válida',
            'detalle' => $type === 'entrada' ? 'Acceso autorizado. Entrada marcada como utilizada.' : 'Consumición autorizada. Canje marcado como utilizado.',
        ]);
    }

    $lookup = $pdo->prepare("SELECT {$column} FROM entradas WHERE codigo_qr = :codigo LIMIT 1");
    $lookup->execute(['codigo' => $code]);
    $entry = $lookup->fetch();

    if ($entry === false) {
        respond(200, [
            'estado' => 'missing',
            'mensaje' => 'Código no encontrado',
            'detalle' => 'No existe ninguna entrada asociada a este código QR.',
        ]);
    }

    respond(200, [
        'estado' => 'repeated',
        'mensaje' => $type === 'entrada' ? 'Entrada ya leída' : 'Consumición ya usada',
        'detalle' => $itemName . ' ya se había validado previamente.',
    ]);
} catch (Throwable $error) {
    error_log($error->getMessage());
    respond(500, ['estado' => 'error', 'mensaje' => 'Error del servidor al validar el código.']);
}

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}