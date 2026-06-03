<?php
require_once __DIR__ . '/_boot.php';

if ($method === 'GET') {
    // GET público: devuelve active_theme_id, custom_themes y public_navbar_config
    $stmt = $db->query("SELECT clave, valor FROM configuraciones_globales WHERE clave IN ('active_theme_id', 'custom_themes', 'public_navbar_config')");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['clave']] = $row['valor'];
    }
    
    $active_theme_id = $config['active_theme_id'] ?? 'tierra_artesanal';
    $custom_themes_json = $config['custom_themes'] ?? '{}';
    $custom_themes = json_decode($custom_themes_json, true) ?: new stdClass();
    
    $public_navbar_json = $config['public_navbar_config'] ?? '{}';
    $public_navbar = json_decode($public_navbar_json, true);
    if (!$public_navbar) {
        $public_navbar = [
            'logo_url' => '',
            'brand_name' => 'Artesanías Sur',
            'link_inicio' => 'Inicio',
            'link_tiendas' => 'Tiendas',
            'link_ofertas' => 'Ofertas',
            'link_productos' => 'Productos',
            'font_family' => 'Outfit',
            'font_size' => '0.95rem'
        ];
    }
    
    json_response([
        'success' => true,
        'active_theme_id' => $active_theme_id,
        'custom_themes' => $custom_themes,
        'public_navbar_config' => $public_navbar
    ]);
}

if ($method === 'PUT') {
    // PUT protegido: solo admin puede guardar active_theme_id, custom_themes y public_navbar_config
    $u = auth();
    role($u, ['admin']);
    
    $d = body();
    
    // Validar y persistir active_theme_id
    if (isset($d['active_theme_id'])) {
        $active_theme_id = trim($d['active_theme_id']);
        if (!empty($active_theme_id)) {
            $stmt = $db->prepare("INSERT INTO configuraciones_globales (clave, valor) VALUES ('active_theme_id', ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$active_theme_id, $active_theme_id]);
        }
    }
    
    // Validar y persistir custom_themes
    if (isset($d['custom_themes'])) {
        $custom_themes = $d['custom_themes'];
        if (is_array($custom_themes)) {
            $custom_themes_json = json_encode($custom_themes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $db->prepare("INSERT INTO configuraciones_globales (clave, valor) VALUES ('custom_themes', ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$custom_themes_json, $custom_themes_json]);
        }
    }
    
    // Validar y persistir public_navbar_config
    if (isset($d['public_navbar_config'])) {
        $public_navbar = $d['public_navbar_config'];
        if (is_array($public_navbar)) {
            $public_navbar_json = json_encode($public_navbar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $db->prepare("INSERT INTO configuraciones_globales (clave, valor) VALUES ('public_navbar_config', ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$public_navbar_json, $public_navbar_json]);
        }
    }
    
    json_response(['success' => true, 'message' => 'Configuración de temas y navbar guardada con éxito.']);
}

json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
