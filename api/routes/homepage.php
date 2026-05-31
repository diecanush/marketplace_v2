<?php
require_once __DIR__ . '/_boot.php';

if ($method === 'GET') {
    // 1. GET Público (Layout activo de la Home)
    if (!isset($_GET['admin'])) {
        $sql = "SELECT l.id, l.component_id, c.name, c.file_path, l.config_payload 
                FROM homepage_layout l 
                JOIN homepage_components c ON c.id = l.component_id 
                WHERE l.is_enabled = 1 
                ORDER BY l.order_index ASC, l.id ASC";
        $stmt = $db->query($sql);
        $data = [];
        while ($row = $stmt->fetch()) {
            $row['config_payload'] = json_decode($row['config_payload'], true);
            $data[] = $row;
        }
        json_response(['success' => true, 'data' => $data]);
    }

    // 2. GET Administrativo (Layout completo y Catálogo de componentes)
    $u = auth();
    role($u, ['admin']);

    $sql = "SELECT l.id, l.component_id, c.name, c.file_path, l.is_enabled, l.order_index, l.config_payload 
            FROM homepage_layout l 
            JOIN homepage_components c ON c.id = l.component_id 
            ORDER BY l.order_index ASC, l.id ASC";
    $stmt = $db->query($sql);
    $layout = [];
    while ($row = $stmt->fetch()) {
        $row['is_enabled'] = (bool)$row['is_enabled'];
        $row['config_payload'] = json_decode($row['config_payload'], true);
        $layout[] = $row;
    }

    $components = $db->query("SELECT * FROM homepage_components ORDER BY name")->fetchAll();
    foreach ($components as &$c) {
        $c['default_config'] = json_decode($c['default_config'], true);
    }

    json_response([
        'success' => true,
        'layout' => $layout,
        'components' => $components
    ]);
}

if ($method === 'PUT') {
    $u = auth();
    role($u, ['admin']);

    $d = body();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // A. PUT Individual (Editar activación o contenido de un componente en el layout)
    if ($id > 0) {
        // Verificar existencia y obtener llaves de configuración del catálogo
        $stmt = $db->prepare("SELECT c.default_config FROM homepage_layout l JOIN homepage_components c ON c.id = l.component_id WHERE l.id = ?");
        $stmt->execute([$id]);
        $comp = $stmt->fetch();
        if (!$comp) {
            json_response(['success' => false, 'message' => 'Componente no encontrado en el diseño.'], 404);
        }

        $is_enabled = isset($d['is_enabled']) ? ($d['is_enabled'] ? 1 : 0) : null;
        $new_payload = $d['config_payload'] ?? null;

        if ($is_enabled === null && $new_payload === null) {
            json_response(['success' => false, 'message' => 'Debe proveer config_payload o is_enabled para actualizar.'], 422);
        }

        $update_fields = [];
        $update_params = [];

        if ($is_enabled !== null) {
            $update_fields[] = "is_enabled = ?";
            $update_params[] = $is_enabled;
        }

        if ($new_payload !== null) {
            if (!is_array($new_payload)) {
                json_response(['success' => false, 'message' => 'config_payload debe ser un objeto JSON válido.'], 422);
            }

            // Validar que las llaves correspondan con el catálogo original
            $default_keys = array_keys(json_decode($comp['default_config'], true));
            $new_keys = array_keys($new_payload);
            sort($default_keys);
            sort($new_keys);

            if ($default_keys !== $new_keys) {
                json_response([
                    'success' => false,
                    'message' => 'Estructura de configuración inválida. Debe contener exactamente las llaves: ' . implode(', ', $default_keys)
                ], 422);
            }

            // Procesamiento seguro de imágenes Base64 dentro del payload
            foreach ($new_payload as $key => $value) {
                if (($key === 'image_url' || $key === 'imagen') && is_string($value)) {
                    if (strpos($value, 'data:image') === 0 && strpos($value, ';base64,') !== false) {
                        $localPath = save_image_from_base64($value, 'layout');
                        if ($localPath) {
                            $new_payload[$key] = $localPath;
                        } else {
                            json_response(['success' => false, 'message' => 'Error al procesar y guardar la imagen base64.'], 500);
                        }
                    }
                }
            }

            $update_fields[] = "config_payload = ?";
            $update_params[] = json_encode($new_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $update_params[] = $id;
        $sql = "UPDATE homepage_layout SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($update_params);

        json_response(['success' => true, 'message' => 'Componente de layout actualizado con éxito.']);
    } 
    
    // B. PUT Masivo (Reordenamiento del Layout)
    else {
        if (!is_array($d) || empty($d)) {
            json_response(['success' => false, 'message' => 'Cuerpo de solicitud inválido para reordenamiento.'], 422);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE homepage_layout SET order_index = ? WHERE id = ?");
            foreach ($d as $item) {
                $lid = isset($item['id']) ? (int)$item['id'] : 0;
                $order = isset($item['order_index']) ? (int)$item['order_index'] : 0;
                if ($lid) {
                    $stmt->execute([$order, $lid]);
                }
            }
            $db->commit();
            json_response(['success' => true, 'message' => 'Layout reordenado correctamente.']);
        } catch (Exception $e) {
            $db->rollBack();
            json_response(['success' => false, 'message' => 'Error al reordenar: ' . $e->getMessage()], 500);
        }
    }
}

if ($method === 'POST') {
    $u = auth();
    role($u, ['admin']);

    $d = body();
    $component_id = isset($d['component_id']) ? (int)$d['component_id'] : 0;
    if ($component_id <= 0) {
        json_response(['success' => false, 'message' => 'ID de componente no válido.'], 422);
    }

    // Obtener default_config del catálogo
    $stmt = $db->prepare("SELECT default_config FROM homepage_components WHERE id = ?");
    $stmt->execute([$component_id]);
    $comp = $stmt->fetch();
    if (!$comp) {
        json_response(['success' => false, 'message' => 'Componente no encontrado en el catálogo.'], 404);
    }

    // Calcular MAX(order_index)
    $maxOrder = (int)$db->query("SELECT MAX(order_index) FROM homepage_layout")->fetchColumn();
    $nextOrder = $maxOrder + 1;

    // Insertar en homepage_layout
    $ins = $db->prepare("INSERT INTO homepage_layout (component_id, is_enabled, order_index, config_payload) VALUES (?, 1, ?, ?)");
    $ins->execute([$component_id, $nextOrder, $comp['default_config']]);

    json_response(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'DELETE') {
    $u = auth();
    role($u, ['admin']);

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'ID de instancia no válido.'], 422);
    }

    $s = $db->prepare("DELETE FROM homepage_layout WHERE id = ?");
    $s->execute([$id]);

    json_response(['success' => true]);
}

json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
