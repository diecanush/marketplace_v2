<?php
require_once __DIR__.'/_boot.php';

function default_store_styles() {
    return [
        'style_primary_color' => '#af4220', // Terracota Rústico por defecto
        'style_button_color' => '#dfa84a',  // Warm Gold por defecto
        'style_text_color' => '#2c2420',    // Dark Clay por defecto
        'style_font_family' => 'Outfit'     // Outfit por defecto
    ];
}

if ($method === 'GET') {
    if (isset($_GET['mine'])) { 
        $u = auth(); 
        $s = $db->prepare('SELECT * FROM tiendas WHERE usuario_id = ? LIMIT 1'); 
        $s->execute([(int)$u['id']]); 
        $row = $s->fetch();
        if ($row) {
            $row['config_diseno'] = json_decode($row['config_diseno'] ?? '', true) ?: default_store_styles();
        }
        json_response(['success' => true, 'data' => $row]); 
    }
    
    if (isset($_GET['id'])) { 
        $s = $db->prepare('SELECT t.*, u.nombre vendedor FROM tiendas t JOIN usuarios u ON u.id = t.usuario_id WHERE t.id = ?'); 
        $s->execute([(int)$_GET['id']]); 
        $row = $s->fetch();
        if ($row) {
            $row['config_diseno'] = json_decode($row['config_diseno'] ?? '', true) ?: default_store_styles();
        }
        json_response(['success' => true, 'data' => $row]); 
    }
    
    $public = isset($_GET['public']); 
    $sql = 'SELECT t.*, u.nombre vendedor FROM tiendas t JOIN usuarios u ON u.id = t.usuario_id ' . 
           ($public ? "WHERE t.activa = 1 AND t.estado = 'activa' " : '') . 
           'ORDER BY t.destacado DESC, t.id DESC';
    $rows = $db->query($sql)->fetchAll();
    foreach ($rows as &$row) {
        $row['config_diseno'] = json_decode($row['config_diseno'] ?? '', true) ?: default_store_styles();
    }
    json_response(['success' => true, 'data' => $rows]);
}

$u = auth();

if ($method === 'PUT') { 
    $id = (int)($_GET['id'] ?? 0); 
    $d = body(); 
    
    if ($u['rol'] !== 'admin') { 
        $o = $db->prepare('SELECT id FROM tiendas WHERE id = ? AND usuario_id = ?'); 
        $o->execute([$id, (int)$u['id']]); 
        if (!$o->fetch()) {
            json_response(['success' => false, 'message' => 'No autorizado'], 403);
        }
    }
    
    // Fetch existing details for safe partial updates
    $existingStmt = $db->prepare('SELECT imagen, banner, config_diseno, destacado, activa, estado FROM tiendas WHERE id = ?');
    $existingStmt->execute([$id]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        json_response(['success' => false, 'message' => 'Tienda no encontrada.'], 404);
    }
    
    $isAdmin = $u['rol'] === 'admin';
    
    // Process image
    if (isset($d['imagen'])) {
        $imagen = save_image_from_base64($d['imagen'], 'tiendas');
        if (empty($imagen) && !empty($d['imagen'])) {
            $imagen = $existing['imagen'];
        }
    } else {
        $imagen = $existing['imagen'];
    }
    
    // Process banner
    if (isset($d['banner'])) {
        $banner = save_image_from_base64($d['banner'], 'tiendas');
        if (empty($banner) && !empty($d['banner'])) {
            $banner = $existing['banner'];
        }
    } else {
        $banner = $existing['banner'];
    }
    
    // Process design config
    if (isset($d['config_diseno'])) {
        $config_diseno = $d['config_diseno'];
        if (!is_array($config_diseno)) {
            json_response(['success' => false, 'message' => 'config_diseno debe ser un objeto JSON válido.'], 422);
        }
        
        $allowed_keys = ['style_primary_color', 'style_button_color', 'style_text_color', 'style_font_family'];
        $new_keys = array_keys($config_diseno);
        $invalid_keys = array_diff($new_keys, $allowed_keys);
        if (!empty($invalid_keys)) {
            json_response([
                'success' => false,
                'message' => 'Estructura de configuración de diseño inválida. Solo se permiten las llaves: ' . implode(', ', $allowed_keys)
            ], 422);
        }
        
        $config_diseno = array_merge(default_store_styles(), $config_diseno);
        $config_diseno_json = json_encode($config_diseno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $config_diseno_json = $existing['config_diseno'];
    }

    // Seguridad de actualización de estados
    $destacado = $isAdmin ? (!empty($d['destacado']) ? 1 : 0) : (int)$existing['destacado'];
    $activa = $isAdmin ? (!empty($d['activa']) ? 1 : 0) : (int)$existing['activa'];
    $estado = $isAdmin ? ($d['estado'] ?? 'activa') : $existing['estado'];
    
    $s = $db->prepare('UPDATE tiendas SET nombre = ?, rubro = ?, descripcion = ?, imagen = ?, banner = ?, whatsapp = ?, instagram = ?, destacado = ?, activa = ?, estado = ?, config_diseno = ? WHERE id = ?'); 
    $s->execute([
        trim($d['nombre'] ?? ''),
        trim($d['rubro'] ?? ''),
        trim($d['descripcion'] ?? ''),
        $imagen,
        $banner,
        trim($d['whatsapp'] ?? ''),
        trim($d['instagram'] ?? ''),
        $destacado,
        $activa,
        $estado,
        $config_diseno_json,
    ]); 
    
    json_response(['success' => true]); 
}

if ($method === 'DELETE') {
    if ($u['rol'] !== 'admin') {
        json_response(['success' => false, 'message' => 'No autorizado.'], 403);
    }
    $id = (int)($_GET['id'] ?? 0);
    $s = $db->prepare('DELETE FROM tiendas WHERE id = ?');
    $s->execute([$id]);
    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
