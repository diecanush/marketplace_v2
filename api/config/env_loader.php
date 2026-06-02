<?php
/**
 * api/config/env_loader.php
 * Cargador de variables de entorno simple y robusto.
 */

function load_env() {
    // Buscar el archivo .env en la raíz del proyecto
    $envPath = realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . '.env';
    
    if (!file_exists($envPath)) {
        // Registrar advertencia en los logs de PHP y continuar con fallbacks del sistema
        error_log("Advertencia: El archivo .env no existe en la ruta: " . $envPath . ". Se utilizarán los valores por defecto del sistema.");
        return;
    }
    
    if (!is_readable($envPath)) {
        error_log("Error: El archivo .env existe en " . $envPath . " pero no tiene permisos de lectura.");
        return;
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("Error: No se pudo leer el contenido del archivo .env.");
        return;
    }
    
    foreach ($lines as $lineNo => $line) {
        $line = trim($line);
        // Ignorar comentarios y líneas vacías
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Validar formato CLAVE=VALOR
        if (strpos($line, '=') === false) {
            error_log("Advertencia de sintaxis en .env (Línea " . ($lineNo + 1) . "): Formato inválido sin signo '='.");
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remover comillas envolventes si existen (ej. "valor" o 'valor')
        if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        // Guardar la variable en el entorno
        if (!empty($name)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Ejecutar la carga
load_env();
