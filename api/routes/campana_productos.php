<?php
require_once __DIR__ . '/_boot.php';

$u = auth();
role($u, ['admin', 'vendedor']);

if ($method === 'POST') {
    $d = body();
    $campanaId = isset($d['campana_id']) ? (int)$d['campana_id'] : 0;
    $productoId = isset($d['producto_id']) ? (int)$d['producto_id'] : 0;
    
    if (!$campanaId || !$productoId) {
        json_response(['success' => false, 'message' => 'Campaña y producto requeridos'], 422);
    }
    
    // Verificar que la campaña existe
    $stmt = $db->prepare("SELECT id FROM campanas WHERE id = ?");
    $stmt->execute([$campanaId]);
    if (!$stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Campaña no encontrada'], 404);
    }
    
    // Verificar que el producto existe
    $stmt = $db->prepare("SELECT tienda_id FROM productos WHERE id = ?");
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();
    if (!$producto) {
        json_response(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }
    
    // Validación de seguridad para vendedores
    if ($u['rol'] === 'vendedor') {
        $stmtStore = $db->prepare("SELECT id FROM tiendas WHERE usuario_id = ?");
        $stmtStore->execute([$u['id']]);
        $tienda = $stmtStore->fetch();
        
        if (!$tienda || $tienda['id'] != $producto['tienda_id']) {
            json_response(['success' => false, 'message' => 'No autorizado para este producto'], 403);
        }
    }
    
    // Insertar la relación (evitando duplicados)
    $stmt = $db->prepare("INSERT IGNORE INTO campana_productos (campana_id, producto_id) VALUES (?, ?)");
    $stmt->execute([$campanaId, $productoId]);
    
    json_response(['success' => true], 201);
}

if ($method === 'DELETE') {
    $campanaId = isset($_GET['campana_id']) ? (int)$_GET['campana_id'] : 0;
    $productoId = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
    
    if (!$campanaId || !$productoId) {
        json_response(['success' => false, 'message' => 'Campaña y producto requeridos'], 422);
    }
    
    // Buscar la relación para verificar su existencia y obtener la tienda asociada
    $stmt = $db->prepare("SELECT cp.*, p.tienda_id 
                          FROM campana_productos cp 
                          JOIN productos p ON p.id = cp.producto_id 
                          WHERE cp.campana_id = ? AND cp.producto_id = ?");
    $stmt->execute([$campanaId, $productoId]);
    $rel = $stmt->fetch();
    
    if (!$rel) {
        json_response(['success' => false, 'message' => 'Asociación no encontrada'], 404);
    }
    
    // Validación de seguridad para vendedores
    if ($u['rol'] === 'vendedor') {
        $stmtStore = $db->prepare("SELECT id FROM tiendas WHERE usuario_id = ?");
        $stmtStore->execute([$u['id']]);
        $tienda = $stmtStore->fetch();
        
        if (!$tienda || $tienda['id'] != $rel['tienda_id']) {
            json_response(['success' => false, 'message' => 'No autorizado para desasociar este producto'], 403);
        }
    }
    
    // Eliminar la relación
    $stmt = $db->prepare("DELETE FROM campana_productos WHERE campana_id = ? AND producto_id = ?");
    $stmt->execute([$campanaId, $productoId]);
    
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Método no permitido'], 405);
