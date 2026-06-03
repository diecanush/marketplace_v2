<?php
if (!defined('JWT_SECRET')) {
  define('JWT_SECRET', getenv('JWT_SECRET') ?: 'CAMBIAR_CLAVE_SECRETA_MARKETPLACE_V2');
}

// Control crítico de seguridad para entorno de producción
if (getenv('APP_ENV') === 'production' && JWT_SECRET === 'CAMBIAR_CLAVE_SECRETA_MARKETPLACE_V2') {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'success' => false,
    'message' => 'Error de Seguridad Crítico: JWT_SECRET conserva el valor por defecto en producción. El sistema ha bloqueado las operaciones hasta que se configure una clave secreta segura.'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
function json_response($payload,$status=200) { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit; }
function body() { $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }
function method() { $m=$_SERVER['REQUEST_METHOD']; $h=function_exists('getallheaders')?getallheaders():[]; return ($m==='POST' && isset($h['X-HTTP-Method-Override'])) ? strtoupper($h['X-HTTP-Method-Override']) : $m; }
function b64e($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64d($d) { $r=strlen($d)%4; if($r)$d.=str_repeat('=',4-$r); return base64_decode(strtr($d,'-_','+/')); }
function token_make($p) { $p['iat']=time(); $p['exp']=time()+604800; $h=b64e(json_encode(['alg'=>'HS256','typ'=>'JWT'])); $b=b64e(json_encode($p)); $s=b64e(hash_hmac('sha256',"$h.$b",JWT_SECRET,true)); return "$h.$b.$s"; }
function token_read($t) { $a=explode('.',$t); if(count($a)!==3)return null; [$h,$b,$s]=$a; $e=b64e(hash_hmac('sha256',"$h.$b",JWT_SECRET,true)); if(!hash_equals($e,$s))return null; $p=json_decode(b64d($b),true); if(!$p || time()>($p['exp']??0))return null; return $p; }
function auth() { $h=function_exists('getallheaders')?getallheaders():[]; $a=$h['Authorization']??$h['authorization']??''; if(!preg_match('/Bearer\s+(.+)/',$a,$m)) json_response(['success'=>false,'message'=>'Token no enviado'],401); $u=token_read($m[1]); if(!$u) json_response(['success'=>false,'message'=>'Token inválido'],401); return $u; }
function optional_auth() { $h=function_exists('getallheaders')?getallheaders():[]; $a=$h['Authorization']??$h['authorization']??''; if(!preg_match('/Bearer\s+(.+)/',$a,$m))return null; return token_read($m[1]); }
function role($u,$roles) { if(!in_array($u['rol']??'', $roles, true)) json_response(['success'=>false,'message'=>'No autorizado'],403); }
function slug($s) { $s=mb_strtolower(trim($s),'UTF-8'); $s=preg_replace('/[^\pL\pN]+/u','-',$s); return trim($s,'-') ?: uniqid('item-'); }

function resize_image_gd($source_path, $dest_path, $max_width, $max_height) {
  if (!extension_loaded('gd')) return false;
  
  list($orig_width, $orig_height, $type) = @getimagesize($source_path);
  if (!$orig_width || !$orig_height) return false;
  
  $ratio = min($max_width / $orig_width, $max_height / $orig_height);
  if ($ratio >= 1.0) {
    $new_width = $orig_width;
    $new_height = $orig_height;
  } else {
    $new_width = round($orig_width * $ratio);
    $new_height = round($orig_height * $ratio);
  }
  
  switch ($type) {
    case IMAGETYPE_JPEG: $src_img = @imagecreatefromjpeg($source_path); break;
    case IMAGETYPE_PNG: $src_img = @imagecreatefrompng($source_path); break;
    case IMAGETYPE_WEBP: $src_img = @imagecreatefromwebp($source_path); break;
    default: return false;
  }
  
  if (!$src_img) return false;
  
  $dst_img = imagecreatetruecolor($new_width, $new_height);
  imagealphablending($dst_img, false);
  imagesavealpha($dst_img, true);
  
  $transparent = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
  imagefill($dst_img, 0, 0, $transparent);
  
  imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
  
  $success = @imagewebp($dst_img, $dest_path, 80);
  
  imagedestroy($src_img);
  imagedestroy($dst_img);
  return $success;
}

function save_image_from_base64($img_str, $folder_name) {
  $img_str = trim($img_str);
  if (empty($img_str)) return '';
  
  if (strpos($img_str, 'uploads/') === 0 || strpos($img_str, 'http://') === 0 || strpos($img_str, 'https://') === 0) {
    return $img_str;
  }
  
  if (strpos($img_str, 'data:image') === 0 && strpos($img_str, ';base64,') !== false) {
    $parts = explode(',', $img_str);
    if (count($parts) < 2) return '';
    
    $meta = $parts[0];
    $ext = 'png';
    if (strpos($meta, 'image/jpeg') !== false || strpos($meta, 'image/jpg') !== false) {
      $ext = 'jpg';
    } elseif (strpos($meta, 'image/webp') !== false) {
      $ext = 'webp';
    }
    
    $file_data = base64_decode($parts[1]);
    if ($file_data === false) return '';
    
    $sha256 = hash('sha256', $file_data);
    $upload_dir = PATH_UPLOADS . DIRECTORY_SEPARATOR . $folder_name;
    if (!file_exists($upload_dir)) {
      if (!@mkdir($upload_dir, 0755, true)) {
        return '';
      }
    }
    
    $converted = false;
    $saved_filename = '';
    
    if (extension_loaded('gd')) {
      $img = @imagecreatefromstring($file_data);
      if ($img !== false) {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        
        $saved_filename = "img_{$sha256}.webp";
        $filepath = $upload_dir . DIRECTORY_SEPARATOR . $saved_filename;
        
        if (file_exists($filepath)) {
          imagedestroy($img);
          return 'uploads/' . $folder_name . '/' . $saved_filename;
        }
        
        $converted = @imagewebp($img, $filepath, 80);
        imagedestroy($img);
      }
    }
    
    if (!$converted) {
      $saved_filename = "img_{$sha256}.{$ext}";
      $filepath = $upload_dir . DIRECTORY_SEPARATOR . $saved_filename;
      
      if (file_exists($filepath)) {
        return 'uploads/' . $folder_name . '/' . $saved_filename;
      }
      
      if (@file_put_contents($filepath, $file_data) === false) {
        return '';
      }
    }
    
    // Generar versiones optimizadas (thumbs y medium)
    $thumbs_dir = $upload_dir . DIRECTORY_SEPARATOR . 'thumbs';
    $medium_dir = $upload_dir . DIRECTORY_SEPARATOR . 'medium';
    
    if (!file_exists($thumbs_dir)) @mkdir($thumbs_dir, 0755, true);
    if (!file_exists($medium_dir)) @mkdir($medium_dir, 0755, true);
    
    resize_image_gd($filepath, $thumbs_dir . DIRECTORY_SEPARATOR . $saved_filename, 300, 300);
    resize_image_gd($filepath, $medium_dir . DIRECTORY_SEPARATOR . $saved_filename, 800, 800);
    
    return 'uploads/' . $folder_name . '/' . $saved_filename;
  }
  return '';
}
