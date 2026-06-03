<?php
/**
 * api/routes/cleanup.php
 * Endpoint administrativo para Garbage Collection de imágenes con modo DRY-RUN obligatorio.
 */
require_once __DIR__ . '/_boot.php';
$u = auth();
role($u, ['admin']);

// 1. Obtener todas las referencias en la base de datos
$referenced_paths = [];

// Helper para limpiar e incorporar rutas
$add_path = function($p) use (&$referenced_paths) {
  if (!$p) return;
  $p = str_replace('\\', '/', trim($p));
  $p = ltrim($p, '/');
  if (strpos($p, 'uploads/') === 0) {
    $referenced_paths[$p] = true;
  }
};

// Helper para extraer de JSON
$add_from_json = function($json_str) use ($add_path) {
  $data = json_decode($json_str, true);
  if (is_array($data)) {
    array_walk_recursive($data, function($val) use ($add_path) {
      if (is_string($val)) {
        $add_path($val);
      }
    });
  }
};

// A. Tiendas
$stmt = $db->query("SELECT imagen, banner FROM tiendas");
while ($r = $stmt->fetch()) {
  $add_path($r['imagen']);
  $add_path($r['banner']);
}

// B. Productos
$stmt = $db->query("SELECT imagen FROM productos");
while ($r = $stmt->fetch()) {
  $add_path($r['imagen']);
}

// C. Campañas
$stmt = $db->query("SELECT imagen FROM campanas");
while ($r = $stmt->fetch()) {
  $add_path($r['imagen']);
}

// D. Solicitudes Vendedor (JSON)
$stmt = $db->query("SELECT imagenes FROM solicitudes_vendedor");
while ($r = $stmt->fetch()) {
  $add_from_json($r['imagenes']);
}

// E. Homepage Layout (JSON)
$stmt = $db->query("SELECT config_payload FROM homepage_layout");
while ($r = $stmt->fetch()) {
  $add_from_json($r['config_payload']);
}

// 2. Escanear archivos físicos en uploads
$orphaned_files = [];
$total_scanned = 0;
$total_orphans_size = 0;

$upload_dir = PATH_UPLOADS;
if (file_exists($upload_dir)) {
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));
  foreach ($iterator as $file) {
    if ($file->isFile()) {
      $filename = $file->getFilename();
      if ($filename === '.gitkeep' || $filename === '.DS_Store' || $filename === 'Thumbs.db') {
        continue;
      }
      
      $total_scanned++;
      $filepath = $file->getRealPath();
      
      // Obtener ruta relativa empezando por 'uploads/'
      $relative_path = '';
      $idx = strpos(str_replace('\\', '/', $filepath), '/uploads/');
      if ($idx !== -1) {
        $relative_path = substr(str_replace('\\', '/', $filepath), $idx + 1);
      }
      
      if (empty($relative_path)) continue;
      
      $is_orphan = true;
      
      // Si la ruta exacta está referenciada, no es huérfana
      if (isset($referenced_paths[$relative_path])) {
        $is_orphan = false;
      } else {
        // Si está dentro de thumbs/ o medium/, comprobar si el original está referenciado
        if (strpos($relative_path, '/thumbs/') !== false || strpos($relative_path, '/medium/') !== false) {
          $original_path = str_replace(['/thumbs/', '/medium/'], '/', $relative_path);
          if (isset($referenced_paths[$original_path])) {
            $is_orphan = false;
          }
        }
      }
      
      if ($is_orphan) {
        $size = $file->getSize();
        $orphaned_files[] = [
          'relative_path' => $relative_path,
          'absolute_path' => $filepath,
          'size' => $size
        ];
        $total_orphans_size += $size;
      }
    }
  }
}

// 3. Determinar modo de ejecución
$confirm = isset($_GET['confirm']) && $_GET['confirm'] == '1';
$deleted_files = [];
$deletion_errors = [];

if ($confirm) {
  // Segunda ejecución: eliminación física real de archivos
  foreach ($orphaned_files as $file_info) {
    if (@unlink($file_info['absolute_path'])) {
      $deleted_files[] = $file_info['relative_path'];
    } else {
      $deletion_errors[] = [
        'path' => $file_info['relative_path'],
        'error' => 'No se pudo eliminar el archivo. Verifique permisos.'
      ];
    }
  }
  
  json_response([
    'success' => true,
    'mode' => 'DELETE',
    'message' => 'Eliminación masiva ejecutada de forma explícita.',
    'total_orphans_detected' => count($orphaned_files),
    'total_deleted' => count($deleted_files),
    'space_freed_bytes' => $total_orphans_size,
    'deleted_files' => $deleted_files,
    'errors' => $deletion_errors
  ]);
} else {
  // Primera ejecución (DRY-RUN obligatorio)
  json_response([
    'success' => true,
    'mode' => 'DRY_RUN',
    'message' => 'Simulación de limpieza obligatoria ejecutada con éxito. No se eliminó ningún archivo físico.',
    'total_scanned_files' => $total_scanned,
    'total_orphans_detected' => count($orphaned_files),
    'space_recoverable_bytes' => $total_orphans_size,
    'orphaned_files' => array_map(function($f) {
      return [
        'path' => $f['relative_path'],
        'size_bytes' => $f['size']
      ];
    }, $orphaned_files),
    'action_required_for_deletion' => BASE_URL . '/api/routes/cleanup.php?confirm=1'
  ]);
}
