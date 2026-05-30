<?php require_once __DIR__.'/_boot.php';
$action=$_GET['action']??'';
if($action==='register'){
  if($method!=='POST') json_response(['success'=>false,'message'=>'Método no permitido'],405);
  $d=body(); $nombre=trim($d['nombre']??''); $email=strtolower(trim($d['email']??'')); $pass=$d['password']??'';
  if(!$nombre || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<6) json_response(['success'=>false,'message'=>'Datos inválidos'],422);
  $s=$db->prepare('SELECT id FROM usuarios WHERE email=?'); $s->execute([$email]); if($s->fetch()) json_response(['success'=>false,'message'=>'Email ya registrado'],409);
  $count=(int)$db->query('SELECT COUNT(*) c FROM usuarios')->fetch()['c']; $rol=$count===0?'admin':'cliente';
  $db->beginTransaction();
  $s=$db->prepare('INSERT INTO usuarios(nombre,email,password_hash,rol,activo) VALUES(?,?,?,?,1)'); $s->execute([$nombre,$email,password_hash($pass,PASSWORD_DEFAULT),$rol]); $uid=(int)$db->lastInsertId();
  $db->commit(); $token=token_make(['id'=>$uid,'nombre'=>$nombre,'email'=>$email,'rol'=>$rol]); json_response(['success'=>true,'token'=>$token,'user'=>['id'=>$uid,'nombre'=>$nombre,'email'=>$email,'rol'=>$rol]],201);
}
if($action==='login'){
  $d=body(); $s=$db->prepare('SELECT * FROM usuarios WHERE email=? AND activo=1'); $s->execute([strtolower(trim($d['email']??''))]); $u=$s->fetch();
  if(!$u || !password_verify($d['password']??'', $u['password_hash'])) json_response(['success'=>false,'message'=>'Email o contraseña incorrectos'],401);
  $token=token_make(['id'=>(int)$u['id'],'nombre'=>$u['nombre'],'email'=>$u['email'],'rol'=>$u['rol']]); json_response(['success'=>true,'token'=>$token,'user'=>['id'=>(int)$u['id'],'nombre'=>$u['nombre'],'email'=>$u['email'],'rol'=>$u['rol']]]);
}
if($action==='me'){ 
  $u=auth(); 
  $s=$db->prepare('SELECT id,nombre,email,rol,activo FROM usuarios WHERE id=?'); 
  $s->execute([(int)$u['id']]); 
  $dbUser=$s->fetch();
  if(!$dbUser || !$dbUser['activo']) {
    json_response(['success'=>false,'message'=>'Usuario inactivo o no encontrado'],401);
  }
  if($dbUser['rol'] !== $u['rol']){
    $token=token_make(['id'=>(int)$dbUser['id'],'nombre'=>$dbUser['nombre'],'email'=>$dbUser['email'],'rol'=>$dbUser['rol']]);
    json_response(['success'=>true,'token'=>$token,'user'=>['id'=>(int)$dbUser['id'],'nombre'=>$dbUser['nombre'],'email'=>$dbUser['email'],'rol'=>$dbUser['rol']]]);
  }
  json_response(['success'=>true,'user'=>['id'=>(int)$dbUser['id'],'nombre'=>$dbUser['nombre'],'email'=>$dbUser['email'],'rol'=>$dbUser['rol']]]); 
}
json_response(['success'=>false,'message'=>'Acción inválida'],404);
