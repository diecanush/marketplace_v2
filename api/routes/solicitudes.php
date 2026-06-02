<?php
require_once __DIR__ . '/_boot.php';

$u = auth(); // Requiere usuario autenticado

// Función para procesar imágenes en Base64 y guardarlas físicamente en la carpeta uploads/solicitudes
function process_uploaded_images($images_array) {
  if (!is_array($images_array)) return [];
  
  $saved_paths = [];
  $upload_dir = PATH_UPLOADS . DIRECTORY_SEPARATOR . 'solicitudes';
  
  if (!file_exists($upload_dir)) {
    if (!@mkdir($upload_dir, 0755, true)) {
      return [];
    }
  }
  
  foreach ($images_array as $img_str) {
    $img_str = trim($img_str);
    if (empty($img_str)) continue;
    
    // Si ya es una ruta relativa válida guardada previamente, conservarla
    if (strpos($img_str, 'uploads/solicitudes/') === 0) {
      $saved_paths[] = $img_str;
      continue;
    }
    
    // Si es una imagen en formato data uri base64, decodificarla y guardarla
    if (strpos($img_str, 'data:image') === 0 && strpos($img_str, ';base64,') !== false) {
      $parts = explode(',', $img_str);
      if (count($parts) < 2) continue;
      
      $meta = $parts[0];
      $ext = 'png';
      if (strpos($meta, 'image/jpeg') !== false || strpos($meta, 'image/jpg') !== false) {
        $ext = 'jpg';
      } elseif (strpos($meta, 'image/webp') !== false) {
        $ext = 'webp';
      }
      
      $file_data = base64_decode($parts[1]);
      if ($file_data === false) continue;
      
      $filename = uniqid('sol_') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $filepath = $upload_dir . '/' . $filename;
      
      if (@file_put_contents($filepath, $file_data) !== false) {
        $saved_paths[] = 'uploads/solicitudes/' . $filename;
      }
    }
  }
  return $saved_paths;
}

if ($method === 'GET') {
  // Conteo de solicitudes pendientes para el administrador (badge de la barra lateral)
  if (isset($_GET['pending_count'])) {
    role($u, ['admin']);
    $s = $db->query("SELECT COUNT(*) c FROM solicitudes_vendedor WHERE estado='pendiente'");
    json_response(['success' => true, 'count' => (int)$s->fetch()['c']]);
  }

  // Obtener la solicitud propia del cliente
  if (isset($_GET['mine'])) {
    $s = $db->prepare("SELECT * FROM solicitudes_vendedor WHERE usuario_id = ? LIMIT 1");
    $s->execute([(int)$u['id']]);
    json_response(['success' => true, 'data' => $s->fetch() ?: null]);
  }

  // Si es admin, puede ver todo
  role($u, ['admin']);
  
  $sql = "SELECT s.*, u.nombre, u.email 
          FROM solicitudes_vendedor s 
          JOIN usuarios u ON u.id = s.usuario_id 
          ORDER BY s.id DESC";
  json_response(['success' => true, 'data' => $db->query($sql)->fetchAll()]);
}

if ($method === 'POST') {
  // Solo clientes pueden postularse
  if ($u['rol'] !== 'cliente') {
    json_response(['success' => false, 'message' => 'Solo los usuarios con rol cliente pueden postularse.'], 403);
  }

  // Verificar si ya existe una solicitud activa
  $chk = $db->prepare("SELECT id, estado FROM solicitudes_vendedor WHERE usuario_id = ? LIMIT 1");
  $chk->execute([(int)$u['id']]);
  $existing = $chk->fetch();

  if ($existing) {
    if ($existing['estado'] === 'pendiente' || $existing['estado'] === 'aprobado') {
      json_response(['success' => false, 'message' => 'Ya tienes una solicitud en proceso o aprobada.'], 422);
    }
  }

  $d = body();
  $nombre_tienda = trim($d['nombre_tienda'] ?? '');
  $rubro = trim($d['rubro'] ?? '');
  $descripcion = trim($d['descripcion'] ?? '');
  $whatsapp = trim($d['whatsapp'] ?? '');
  $instagram = trim($d['instagram'] ?? '');
  $raw_imagenes = $d['imagenes'] ?? [];

  if (!$nombre_tienda) {
    json_response(['success' => false, 'message' => 'El nombre de la tienda es obligatorio.'], 422);
  }

  // Procesar imágenes
  $saved_images = process_uploaded_images($raw_imagenes);

  // Validación: si no tiene instagram, debe subir al menos una imagen de sus productos
  if (empty($instagram) && empty($saved_images)) {
    json_response(['success' => false, 'message' => 'Si no tienes cuenta de Instagram, debes subir al menos una imagen de tus productos.'], 422);
  }

  $imagenes_json = json_encode($saved_images, JSON_UNESCAPED_SLASHES);

  if ($existing) {
    // Reutilizar registro si ya existía uno rechazado o en corrección
    $s = $db->prepare("UPDATE solicitudes_vendedor 
                       SET nombre_tienda=?, rubro=?, descripcion=?, whatsapp=?, instagram=?, imagenes=?, estado='pendiente', comentarios_admin=NULL 
                       WHERE usuario_id=?");
    $s->execute([$nombre_tienda, $rubro, $descripcion, $whatsapp, $instagram, $imagenes_json, (int)$u['id']]);
    $id = (int)$existing['id'];
  } else {
    // Insertar uno nuevo
    $s = $db->prepare("INSERT INTO solicitudes_vendedor (usuario_id, nombre_tienda, rubro, descripcion, whatsapp, instagram, imagenes, estado) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $s->execute([(int)$u['id'], $nombre_tienda, $rubro, $descripcion, $whatsapp, $instagram, $imagenes_json]);
    $id = (int)$db->lastInsertId();
  }

  json_response(['success' => true, 'id' => $id, 'message' => 'Solicitud enviada correctamente.'], 201);
}

if ($method === 'PUT') {
  $id = (int)($_GET['id']??0);
  if (!$id) json_response(['success' => false, 'message' => 'ID de solicitud no provisto.'], 422);

  $d = body();
  
  // Caso de cliente que reenvía tras corrección
  if ($u['rol'] === 'cliente') {
    // Validar propiedad de la solicitud
    $chk = $db->prepare("SELECT id, estado FROM solicitudes_vendedor WHERE id = ? AND usuario_id = ? LIMIT 1");
    $chk->execute([$id, (int)$u['id']]);
    $sol = $chk->fetch();
    
    if (!$sol) json_response(['success' => false, 'message' => 'Solicitud no encontrada.'], 404);
    if (!in_array($sol['estado'], ['correcion', 'rechazado'])) {
      json_response(['success' => false, 'message' => 'No puedes editar una solicitud en revisión o aprobada.'], 422);
    }

    $nombre_tienda = trim($d['nombre_tienda'] ?? '');
    $rubro = trim($d['rubro'] ?? '');
    $descripcion = trim($d['descripcion'] ?? '');
    $whatsapp = trim($d['whatsapp'] ?? '');
    $instagram = trim($d['instagram'] ?? '');
    $raw_imagenes = $d['imagenes'] ?? [];

    if (!$nombre_tienda) {
      json_response(['success' => false, 'message' => 'El nombre de la tienda es obligatorio.'], 422);
    }

    // Procesar imágenes
    $saved_images = process_uploaded_images($raw_imagenes);

    // Validación
    if (empty($instagram) && empty($saved_images)) {
      json_response(['success' => false, 'message' => 'Si no tienes cuenta de Instagram, debes subir al menos una imagen de tus productos.'], 422);
    }

    $imagenes_json = json_encode($saved_images, JSON_UNESCAPED_SLASHES);

    $s = $db->prepare("UPDATE solicitudes_vendedor 
                       SET nombre_tienda=?, rubro=?, descripcion=?, whatsapp=?, instagram=?, imagenes=?, estado='pendiente', comentarios_admin=NULL 
                       WHERE id=?");
    $s->execute([$nombre_tienda, $rubro, $descripcion, $whatsapp, $instagram, $imagenes_json, $id]);
    json_response(['success' => true, 'message' => 'Solicitud reenviada con éxito.']);
  }

  // Caso de Administrador: aprueba, rechaza, edita o solicita correcciones
  role($u, ['admin']);

  $chk = $db->prepare("SELECT s.*, u.nombre user_nombre, u.email user_email 
                       FROM solicitudes_vendedor s 
                       JOIN usuarios u ON u.id = s.usuario_id 
                       WHERE s.id = ? LIMIT 1");
  $chk->execute([$id]);
  $sol = $chk->fetch();

  if (!$sol) json_response(['success' => false, 'message' => 'Solicitud no encontrada.'], 404);

  $nombre_tienda = trim($d['nombre_tienda'] ?? $sol['nombre_tienda']);
  $rubro = trim($d['rubro'] ?? $sol['rubro']);
  $descripcion = trim($d['descripcion'] ?? $sol['descripcion']);
  $whatsapp = trim($d['whatsapp'] ?? $sol['whatsapp']);
  $instagram = trim($d['instagram'] ?? $sol['instagram']);
  $estado = $d['estado'] ?? $sol['estado'];
  $comentarios_admin = isset($d['comentarios_admin']) ? trim($d['comentarios_admin']) : $sol['comentarios_admin'];
  
  // Procesar imágenes de admin si envía nuevas
  $raw_imagenes = isset($d['imagenes']) ? $d['imagenes'] : (json_decode($sol['imagenes'], true) ?: []);
  $saved_images = process_uploaded_images($raw_imagenes);
  $imagenes_json = json_encode($saved_images, JSON_UNESCAPED_SLASHES);

  if (!in_array($estado, ['pendiente', 'aprobado', 'rechazado', 'correcion'])) {
    json_response(['success' => false, 'message' => 'Estado de solicitud inválido.'], 422);
  }

  $db->beginTransaction();

  // Guardar cambios en la solicitud
  $s = $db->prepare("UPDATE solicitudes_vendedor 
                     SET nombre_tienda=?, rubro=?, descripcion=?, whatsapp=?, instagram=?, imagenes=?, estado=?, comentarios_admin=? 
                     WHERE id=?");
  $s->execute([$nombre_tienda, $rubro, $descripcion, $whatsapp, $instagram, $imagenes_json, $estado, $comentarios_admin, $id]);

  $emailEnviado = false;

  // Lógica si se aprueba
  if ($estado === 'aprobado' && $sol['estado'] !== 'aprobado') {
    $uid = (int)$sol['usuario_id'];
    
    // 1. Cambiar rol a vendedor en la tabla usuarios
    $sUser = $db->prepare("UPDATE usuarios SET rol = 'vendedor' WHERE id = ?");
    $sUser->execute([$uid]);

    // 2. Crear tienda
    // Verificar si ya tiene tienda registrada
    $sShopCheck = $db->prepare("SELECT id FROM tiendas WHERE usuario_id = ?");
    $sShopCheck->execute([$uid]);
    $shop = $sShopCheck->fetch();

    // Obtener la primera imagen de la postulación para establecerla como imagen de la tienda
    $store_image = null;
    if (!empty($saved_images) && is_array($saved_images)) {
      $store_image = $saved_images[0];
    }

    if (!$shop) {
      $sShop = $db->prepare("INSERT INTO tiendas(usuario_id, nombre, rubro, descripcion, imagen, whatsapp, instagram, activa, estado, destacado) 
                             VALUES(?, ?, ?, ?, ?, ?, ?, 1, 'activa', 0)");
      $sShop->execute([$uid, $nombre_tienda, $rubro, $descripcion, $store_image, $whatsapp, $instagram]);
    } else {
      $sShop = $db->prepare("UPDATE tiendas 
                             SET nombre=?, rubro=?, descripcion=?, imagen=COALESCE(?, imagen), whatsapp=?, instagram=?, activa=1, estado='activa' 
                             WHERE usuario_id=?");
      $sShop->execute([$nombre_tienda, $rubro, $descripcion, $store_image, $whatsapp, $instagram, $uid]);
    }
    
    $emailEnviado = true;
    $mailType = 'aprobado';
  } elseif (in_array($estado, ['correcion', 'rechazado']) && $sol['estado'] !== $estado) {
    $emailEnviado = true;
    $mailType = $estado;
  }

  $db->commit();

  // Enviar correo de notificación (si aplica)
  if ($emailEnviado) {
    $to = $sol['user_email'];
    $headers = "From: no-reply@artesaniassur.com\r\n";
    $headers .= "Reply-To: soporte@artesaniassur.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if ($mailType === 'aprobado') {
      $subject = "¡Felicidades! Tu solicitud de vendedor fue aprobada - Artesanías Sur";
      $msgMail = "Hola {$sol['user_nombre']},\n\n";
      $msgMail .= "Nos complace informarte que tu solicitud para convertirte en vendedor en Artesanías Sur ha sido aprobada con éxito.\n\n";
      $msgMail .= "Tu tienda ya se encuentra activa y visible en la plataforma. Puedes iniciar sesión y acceder a tu panel de administración de vendedor para empezar a cargar tus productos de forma inmediata.\n\n";
      $msgMail .= "Detalles de tu tienda creada:\n";
      $msgMail .= "- Nombre: {$nombre_tienda}\n";
      $msgMail .= "- Especialidad: {$rubro}\n\n";
      $msgMail .= "¡Mucho éxito con tu emprendimiento!\n\nEl equipo de Artesanías Sur.";
    } elseif ($mailType === 'correcion') {
      $subject = "Tu solicitud requiere correcciones - Artesanías Sur";
      $msgMail = "Hola {$sol['user_nombre']},\n\n";
      $msgMail .= "El equipo de administración ha revisado tu solicitud para ser vendedor y ha determinado que requiere algunos ajustes antes de poder ser aprobada.\n\n";
      $msgMail .= "Comentarios del administrador:\n";
      $msgMail .= "\"{$comentarios_admin}\"\n\n";
      $msgMail .= "Por favor, inicia sesión en tu panel, realiza las correcciones solicitadas en el formulario y reenvíalo para una nueva evaluación.\n\n";
      $msgMail .= "Atentamente,\nEl equipo de Artesanías Sur.";
    } else { // rechazado
      $subject = "Estado de tu solicitud de vendedor - Artesanías Sur";
      $msgMail = "Hola {$sol['user_nombre']},\n\n";
      $msgMail .= "Lamentamos informarte que tu solicitud para ser vendedor en Artesanías Sur no ha sido aprobada en esta oportunidad.\n\n";
      if (!empty($comentarios_admin)) {
        $msgMail .= "Motivos de la decisión:\n";
        $msgMail .= "\"{$comentarios_admin}\"\n\n";
      }
      $msgMail .= "Si tienes alguna consulta, puedes contactar con nuestro soporte respondiendo a este correo.\n\n";
      $msgMail .= "Atentamente,\nEl equipo de Artesanías Sur.";
    }
    
    @mail($to, $subject, $msgMail, $headers);
  }

  json_response(['success' => true, 'message' => 'Solicitud actualizada correctamente.']);
}

json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
