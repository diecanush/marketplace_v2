<?php
/**
 * api/config/paths.php
 * Configuración centralizada de rutas virtuales y físicas.
 */

if (!defined('BASE_URL')) {
    // 1. Detección automática del protocolo y host en servidores web
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // 2. Detección del directorio base de manera dinámica (ej. /marketplace_v2)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $apiPosition = strpos($scriptName, '/api/');
    if ($apiPosition !== false) {
        $basePath = substr($scriptName, 0, $apiPosition);
    } else {
        $basePath = '';
    }
    
    // 3. URLs Base (Rutas virtuales para enlaces)
    $baseUrl = getenv('APP_BASE_URL') ?: ($protocol . $host . $basePath);
    $baseUrl = rtrim($baseUrl, '/');
    
    define('BASE_URL', $baseUrl);
    define('API_BASE_URL', BASE_URL . '/api/routes');
    define('UPLOADS_BASE_URL', BASE_URL . '/uploads');
    define('ASSETS_BASE_URL', BASE_URL . '/frontend/assets');

    // 4. Rutas Físicas Absolutas en el Servidor (Para almacenamiento local)
    define('PATH_ROOT', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2));
    define('PATH_UPLOADS', PATH_ROOT . DIRECTORY_SEPARATOR . 'uploads');
    define('PATH_API', PATH_ROOT . DIRECTORY_SEPARATOR . 'api');
    define('PATH_FRONTEND', PATH_ROOT . DIRECTORY_SEPARATOR . 'frontend');
}
