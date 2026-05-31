<?php
require_once __DIR__ . '/_boot.php';

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id > 0) {
        // Obtener detalles de una campaña específica
        $stmt = $db->prepare("SELECT * FROM campanas WHERE id = ?");
        $stmt->execute([$id]);
        $campana = $stmt->fetch();
        
        if (!$campana) {
            json_response(['success' => false, 'message' => 'Campaña no encontrada'], 404);
        }
        
        // Obtener productos asociados a la campaña
        $sqlProducts = "SELECT p.*, t.nombre AS tienda_nombre 
                        FROM productos p 
                        JOIN campana_productos cp ON cp.producto_id = p.id 
                        JOIN tiendas t ON t.id = p.tienda_id 
                        WHERE cp.campana_id = ?";
                        
        if (!isset($_GET['all'])) {
            // Filtrar solo productos activos y tiendas activas en producción
            $sqlProducts .= " AND p.activo = 1 AND t.activa = 1 AND t.estado = 'activa'";
        }
        
        $sProducts = $db->prepare($sqlProducts);
        $sProducts->execute([$id]);
        $campana['productos'] = $sProducts->fetchAll();
        
        json_response(['success' => true, 'data' => $campana]);
    } else {
        // Listado de campañas con conteo de productos asociados
        $sql = "SELECT c.*, COUNT(cp.producto_id) AS total_productos 
                FROM campanas c 
                LEFT JOIN campana_productos cp ON cp.campana_id = c.id";
        
        if (isset($_GET['active']) && $_GET['active'] == 1) {
            $sql .= " WHERE c.activa = 1 
                      AND (c.fecha_inicio IS NULL OR c.fecha_inicio <= CURDATE()) 
                      AND (c.fecha_fin IS NULL OR c.fecha_fin >= CURDATE())";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.fecha_creacion DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        
        $campanas = $stmt->fetchAll();
        foreach ($campanas as &$camp) {
            $camp['total_productos'] = (int)$camp['total_productos'];
        }
        
        json_response(['success' => true, 'data' => $campanas]);
    }
}

// Para mutaciones se requiere autenticación de administrador
$u = auth();
role($u, ['admin']);

if ($method === 'POST') {
    $d = body();
    $nombre = trim($d['nombre'] ?? '');
    
    if (!$nombre) {
        json_response(['success' => false, 'message' => 'Nombre obligatorio'], 422);
    }
    
    $imagenUrl = '';
    if (!empty($d['imagen'])) {
        $imagenUrl = save_image_from_base64($d['imagen'], 'campanas');
    }
    
    $stmt = $db->prepare("INSERT INTO campanas (nombre, descripcion, imagen, activa, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $nombre,
        $d['descripcion'] ?? null,
        $imagenUrl ?: null,
        isset($d['activa']) ? ($d['activa'] ? 1 : 0) : 1,
        !empty($d['fecha_inicio']) ? $d['fecha_inicio'] : null,
        !empty($d['fecha_fin']) ? $d['fecha_fin'] : null
    ]);
    
    json_response(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $stmt = $db->prepare("SELECT * FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    $campana = $stmt->fetch();
    
    if (!$campana) {
        json_response(['success' => false, 'message' => 'Campaña no encontrada'], 404);
    }
    
    $d = body();
    $nombre = isset($d['nombre']) ? trim($d['nombre']) : $campana['nombre'];
    
    if (!$nombre) {
        json_response(['success' => false, 'message' => 'Nombre obligatorio'], 422);
    }
    
    $imagenUrl = $campana['imagen'];
    if (isset($d['imagen']) && !empty($d['imagen'])) {
        $newImg = save_image_from_base64($d['imagen'], 'campanas');
        if ($newImg) {
            $imagenUrl = $newImg;
            // Eliminar imagen vieja
            if ($campana['imagen'] && strpos($campana['imagen'], 'uploads/') === 0) {
                @unlink(__DIR__ . '/../../' . $campana['imagen']);
            }
        }
    }
    
    $stmt = $db->prepare("UPDATE campanas SET nombre = ?, descripcion = ?, imagen = ?, activa = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ?");
    $stmt->execute([
        $nombre,
        $d['descripcion'] ?? $campana['descripcion'],
        $imagenUrl,
        isset($d['activa']) ? ($d['activa'] ? 1 : 0) : $campana['activa'],
        !empty($d['fecha_inicio']) ? $d['fecha_inicio'] : null,
        !empty($d['fecha_fin']) ? $d['fecha_fin'] : null,
        $id
    ]);
    
    json_response(['success' => true]);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $stmt = $db->prepare("SELECT imagen FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    $campana = $stmt->fetch();
    
    if (!$campana) {
        json_response(['success' => false, 'message' => 'Campaña no encontrada'], 404);
    }
    
    if ($campana['imagen'] && strpos($campana['imagen'], 'uploads/') === 0) {
        @unlink(__DIR__ . '/../../' . $campana['imagen']);
    }
    
    $stmt = $db->prepare("DELETE FROM campanas WHERE id = ?");
    $stmt->execute([$id]);
    
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Método no permitido'], 405);
