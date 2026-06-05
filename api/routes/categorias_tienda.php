<?php
require_once __DIR__ . '/_boot.php';

// Helper para obtener la tienda del usuario logueado
function mystore($db, $uid) {
    $s = $db->prepare('SELECT id FROM tiendas WHERE usuario_id = ? LIMIT 1');
    $s->execute([$uid]);
    $t = $s->fetch();
    if (!$t) {
        json_response(['success' => false, 'message' => 'No tenés tienda asociada.'], 422);
    }
    return (int)$t['id'];
}

// Helper para validar propiedad de una categoría
function own_category($db, $uid, $cat_id) {
    $s = $db->prepare('SELECT c.id FROM categorias_tienda c JOIN tiendas t ON t.id = c.tienda_id WHERE c.id = ? AND t.usuario_id = ?');
    $s->execute([$cat_id, $uid]);
    return (bool)$s->fetch();
}

// 1. GET - Listado de categorías
if ($method === 'GET') {
    // Escenario A: Consulta pública por tienda_id
    if (isset($_GET['tienda_id'])) {
        $tienda_id = (int)$_GET['tienda_id'];
        $s = $db->prepare('SELECT * FROM categorias_tienda WHERE tienda_id = ? ORDER BY id ASC');
        $s->execute([$tienda_id]);
        json_response(['success' => true, 'data' => $s->fetchAll()]);
    }

    // Escenario B: Consulta privada del vendedor
    if (isset($_GET['mine'])) {
        $u = auth();
        role($u, ['vendedor', 'admin']);
        
        $tid = mystore($db, (int)$u['id']);
        $s = $db->prepare('SELECT * FROM categorias_tienda WHERE tienda_id = ? ORDER BY id ASC');
        $s->execute([$tid]);
        json_response(['success' => true, 'data' => $s->fetchAll()]);
    }

    json_response(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
}

// Para POST, PUT, DELETE se requiere autenticación y rol
$u = auth();
role($u, ['vendedor', 'admin']);

// 2. POST - Crear categoría
if ($method === 'POST') {
    $d = body();
    $nombre = isset($d['nombre']) ? trim($d['nombre']) : '';
    if (empty($nombre)) {
        json_response(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.'], 422);
    }

    $tid = mystore($db, (int)$u['id']);

    $s = $db->prepare('INSERT INTO categorias_tienda (tienda_id, nombre) VALUES (?, ?)');
    $s->execute([$tid, $nombre]);

    json_response([
        'success' => true,
        'message' => 'Categoría de tienda creada con éxito.',
        'id' => (int)$db->lastInsertId()
    ], 201);
}

// 3. PUT - Editar nombre de categoría
if ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'ID de categoría inválido.'], 422);
    }

    $d = body();
    $nombre = isset($d['nombre']) ? trim($d['nombre']) : '';
    if (empty($nombre)) {
        json_response(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.'], 422);
    }

    // Validar propiedad (los admins pueden saltarse esto)
    if ($u['rol'] !== 'admin' && !own_category($db, (int)$u['id'], $id)) {
        json_response(['success' => false, 'message' => 'No autorizado para modificar esta categoría.'], 403);
    }

    $s = $db->prepare('UPDATE categorias_tienda SET nombre = ? WHERE id = ?');
    $s->execute([$nombre, $id]);

    json_response(['success' => true, 'message' => 'Categoría de tienda actualizada con éxito.']);
}

// 4. DELETE - Eliminar categoría
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'ID de categoría inválido.'], 422);
    }

    // Validar propiedad (los admins pueden saltarse esto)
    if ($u['rol'] !== 'admin' && !own_category($db, (int)$u['id'], $id)) {
        json_response(['success' => false, 'message' => 'No autorizado para eliminar esta categoría.'], 403);
    }

    $s = $db->prepare('DELETE FROM categorias_tienda WHERE id = ?');
    $s->execute([$id]);

    json_response(['success' => true, 'message' => 'Categoría de tienda eliminada con éxito.']);
}

json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
