<?php
require_once __DIR__ . '/_boot.php';

function mystore($db, $uid) {
    $s = $db->prepare('SELECT id FROM tiendas WHERE usuario_id = ? LIMIT 1');
    $s->execute([$uid]);
    $t = $s->fetch();
    if (!$t) {
        json_response(['success' => false, 'message' => 'No tenés tienda asociada.'], 422);
    }
    return (int)$t['id'];
}

function ownprod($db, $uid, $pid) {
    $s = $db->prepare('SELECT p.id FROM productos p JOIN tiendas t ON t.id = p.tienda_id WHERE p.id = ? AND t.usuario_id = ?');
    $s->execute([$pid, $uid]);
    return (bool)$s->fetch();
}

if ($method === 'GET') {
    $u = optional_auth();
    $w = [];
    $p = [];
    if (isset($_GET['mine'])) {
        if (!$u) {
            json_response(['success' => false, 'message' => 'Requiere login.'], 401);
        }
        $w[] = 't.usuario_id = ?';
        $p[] = (int)$u['id'];
    } else {
        $w[] = 'p.activo = 1';
        $w[] = 't.activa = 1';
        $w[] = "t.estado = 'activa'";
    }

    if (isset($_GET['tienda_id'])) {
        $w[] = 'p.tienda_id = ?';
        $p[] = (int)$_GET['tienda_id'];
    }
    if (isset($_GET['categoria_id'])) {
        $w[] = 'p.categoria_id = ?';
        $p[] = (int)$_GET['categoria_id'];
    }
    if (isset($_GET['oferta'])) {
        $w[] = 'p.oferta = 1';
    }

    $sql = 'SELECT p.*, t.nombre tienda, t.whatsapp tienda_whatsapp, c.nombre categoria, ct.nombre categoria_tienda_nombre 
            FROM productos p 
            JOIN tiendas t ON t.id = p.tienda_id 
            LEFT JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN categorias_tienda ct ON ct.id = p.categoria_tienda_id';

    if ($w) {
        $sql .= ' WHERE ' . implode(' AND ', $w);
    }
    $sql .= ' ORDER BY p.destacado DESC, p.id DESC';

    $s = $db->prepare($sql);
    $s->execute($p);
    json_response(['success' => true, 'data' => $s->fetchAll()]);
}

$u = auth();
role($u, ['admin', 'vendedor']);

if ($method === 'POST') {
    $d = body();
    $tid = $u['rol'] === 'admin' && !empty($d['tienda_id']) ? (int)$d['tienda_id'] : mystore($db, (int)$u['id']);

    // Validar que la categoría de la tienda pertenezca a la tienda del producto
    $cat_tienda_id = null;
    if (!empty($d['categoria_tienda_id'])) {
        $cat_tienda_id = (int)$d['categoria_tienda_id'];
        $s_check = $db->prepare('SELECT tienda_id FROM categorias_tienda WHERE id = ?');
        $s_check->execute([$cat_tienda_id]);
        $cat_tienda = $s_check->fetch();
        if (!$cat_tienda || (int)$cat_tienda['tienda_id'] !== $tid) {
            json_response(['success' => false, 'message' => 'La categoría interna seleccionada no pertenece a tu tienda.'], 422);
        }
    }

    $imagen = save_image_from_base64($d['imagen'] ?? '', 'productos');

    $s = $db->prepare('INSERT INTO productos(tienda_id, categoria_id, categoria_tienda_id, nombre, descripcion, precio, stock, imagen, oferta, destacado, activo) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $s->execute([
        $tid,
        $d['categoria_id'] ?: null,
        $cat_tienda_id,
        trim($d['nombre'] ?? ''),
        trim($d['descripcion'] ?? ''),
        (float)($d['precio'] ?? 0),
        (int)($d['stock'] ?? 0),
        $imagen,
        !empty($d['oferta']) ? 1 : 0,
        !empty($d['destacado']) ? 1 : 0,
        !empty($d['activo']) ? 1 : 0
    ]);

    json_response(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if ($u['rol'] !== 'admin' && !ownprod($db, (int)$u['id'], $id)) {
        json_response(['success' => false, 'message' => 'No autorizado.'], 403);
    }

    $d = body();

    // Obtener la tienda del producto para validar pertenencia
    $s_prod = $db->prepare('SELECT tienda_id FROM productos WHERE id = ?');
    $s_prod->execute([$id]);
    $existing_prod = $s_prod->fetch();
    if (!$existing_prod) {
        json_response(['success' => false, 'message' => 'Producto no encontrado.'], 404);
    }
    $tid = (int)$existing_prod['tienda_id'];

    // Validar que la categoría de la tienda pertenezca a la tienda del producto
    $cat_tienda_id = null;
    if (!empty($d['categoria_tienda_id'])) {
        $cat_tienda_id = (int)$d['categoria_tienda_id'];
        $s_check = $db->prepare('SELECT tienda_id FROM categorias_tienda WHERE id = ?');
        $s_check->execute([$cat_tienda_id]);
        $cat_tienda = $s_check->fetch();
        if (!$cat_tienda || (int)$cat_tienda['tienda_id'] !== $tid) {
            json_response(['success' => false, 'message' => 'La categoría interna seleccionada no pertenece a la tienda del producto.'], 422);
        }
    }

    $imagen = save_image_from_base64($d['imagen'] ?? '', 'productos');

    $s = $db->prepare('UPDATE productos SET categoria_id = ?, categoria_tienda_id = ?, nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, oferta = ?, destacado = ?, activo = ? WHERE id = ?');
    $s->execute([
        $d['categoria_id'] ?: null,
        $cat_tienda_id,
        trim($d['nombre'] ?? ''),
        trim($d['descripcion'] ?? ''),
        (float)($d['precio'] ?? 0),
        (int)($d['stock'] ?? 0),
        $imagen,
        !empty($d['oferta']) ? 1 : 0,
        !empty($d['destacado']) ? 1 : 0,
        !empty($d['activo']) ? 1 : 0,
        $id
    ]);

    json_response(['success' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($u['rol'] !== 'admin' && !ownprod($db, (int)$u['id'], $id)) {
        json_response(['success' => false, 'message' => 'No autorizado.'], 403);
    }
    $s = $db->prepare('DELETE FROM productos WHERE id = ?');
    $s->execute([$id]);
    json_response(['success' => true]);
}
