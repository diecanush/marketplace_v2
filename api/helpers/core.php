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
    
    $upload_dir = PATH_UPLOADS . DIRECTORY_SEPARATOR . $folder_name;
    if (!file_exists($upload_dir)) {
      if (!@mkdir($upload_dir, 0755, true)) {
        return '';
      }
    }
    
    $filename = uniqid('img_') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $upload_dir . '/' . $filename;
    
    if (@file_put_contents($filepath, $file_data) !== false) {
      return 'uploads/' . $folder_name . '/' . $filename;
    }
  }
  return '';
}
