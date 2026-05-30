<?php
require_once __DIR__ . '/api/config/db.php';
require_once __DIR__ . '/api/helpers/core.php';

$db = (new Database())->connect();
$s = $db->query("SELECT id, nombre, email, rol FROM usuarios WHERE rol='admin' LIMIT 1");
$u = $s->fetch();
if (!$u) {
    echo "No admin user found\n";
    exit;
}

$token = token_make([
    'id' => (int)$u['id'],
    'nombre' => $u['nombre'],
    'email' => $u['email'],
    'rol' => $u['rol']
]);

echo json_encode([
    'token' => $token,
    'user' => [
        'id' => (int)$u['id'],
        'nombre' => $u['nombre'],
        'email' => $u['email'],
        'rol' => $u['rol']
    ]
]);
