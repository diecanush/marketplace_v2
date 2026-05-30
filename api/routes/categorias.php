<?php require_once __DIR__.'/_boot.php';
if($method==='GET'){ $all=isset($_GET['all']); $sql='SELECT * FROM categorias '.($all?'':'WHERE activa=1').' ORDER BY orden,nombre'; json_response(['success'=>true,'data'=>$db->query($sql)->fetchAll()]); }
$u=auth(); role($u,['admin']);
if($method==='POST'){ $d=body(); $n=trim($d['nombre']??''); if(!$n) json_response(['success'=>false,'message'=>'Nombre obligatorio'],422); $s=$db->prepare('INSERT INTO categorias(nombre,slug,icono,activa,orden) VALUES(?,?,?,?,?)'); $s->execute([$n,slug($d['slug']??$n),$d['icono']??'',!empty($d['activa'])?1:0,(int)($d['orden']??0)]); json_response(['success'=>true,'id'=>(int)$db->lastInsertId()],201); }
if($method==='PUT'){ $id=(int)($_GET['id']??0); $d=body(); $n=trim($d['nombre']??''); $s=$db->prepare('UPDATE categorias SET nombre=?, slug=?, icono=?, activa=?, orden=? WHERE id=?'); $s->execute([$n,slug($d['slug']??$n),$d['icono']??'',!empty($d['activa'])?1:0,(int)($d['orden']??0),$id]); json_response(['success'=>true]); }
if($method==='DELETE'){ $s=$db->prepare('DELETE FROM categorias WHERE id=?'); $s->execute([(int)($_GET['id']??0)]); json_response(['success'=>true]); }
