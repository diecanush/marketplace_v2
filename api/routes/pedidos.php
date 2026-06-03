<?php require_once __DIR__.'/_boot.php';
if($method==='POST'){
  $d=body(); $items=$d['items']??[]; $cli=$d['cliente']??[]; if(!$items) json_response(['success'=>false,'message'=>'Carrito vacío'],422);
  $ids=array_values(array_unique(array_map(fn($x)=>(int)$x['producto_id'],$items))); $in=implode(',',array_fill(0,count($ids),'?'));
  $s=$db->prepare("SELECT p.*,t.nombre tienda FROM productos p JOIN tiendas t ON t.id=p.tienda_id WHERE p.id IN ($in) AND p.activo=1"); $s->execute($ids); $prod=[]; foreach($s->fetchAll() as $p)$prod[(int)$p['id']]=$p;
  
  // Validar stock antes de crear el pedido
  foreach ($items as $it) {
    $pid = (int)$it['producto_id'];
    if (!isset($prod[$pid])) {
      json_response(['success' => false, 'message' => 'Producto no disponible o inactivo.'], 422);
    }
    $cant = max(1, (int)($it['cantidad'] ?? 1));
    if ($prod[$pid]['stock'] < $cant) {
      json_response([
        'success' => false, 
        'message' => "Stock insuficiente para el producto '{$prod[$pid]['nombre']}'. Disponible: {$prod[$pid]['stock']}, Solicitado: {$cant}."
      ], 422);
    }
  }

  $por=[]; foreach($items as $it){$pid=(int)$it['producto_id']; if(!isset($prod[$pid]))continue; $c=max(1,(int)($it['cantidad']??1)); $por[(int)$prod[$pid]['tienda_id']][]=[$prod[$pid],$c];}
  
  $db->beginTransaction();
  try {
    $creados=[]; 
    foreach($por as $tid=>$arr){ 
      $total=0; 
      foreach($arr as $x)$total+=(float)$x[0]['precio']*$x[1]; 
      $s=$db->prepare("INSERT INTO pedidos(cliente_nombre,cliente_email,cliente_telefono,tienda_id,total,estado,observaciones) VALUES(?,?,?,?,?,'nuevo',?)"); 
      $s->execute([trim($cli['nombre']??''),trim($cli['email']??''),trim($cli['telefono']??''),$tid,$total,trim($d['observaciones']??'')]); 
      $pid=(int)$db->lastInsertId(); 
      
      $i=$db->prepare('INSERT INTO pedido_items(pedido_id,producto_id,cantidad,precio_unitario) VALUES(?,?,?,?)');
      $stmtStock=$db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
      
      foreach($arr as $x){
        $prod_id = (int)$x[0]['id'];
        $cant = $x[1];
        
        $stmtStock->execute([$cant, $prod_id, $cant]);
        if ($stmtStock->rowCount() === 0) {
          throw new Exception("Stock insuficiente de último minuto para el producto: " . $x[0]['nombre']);
        }
        
        $i->execute([$pid,$prod_id,$cant,(float)$x[0]['precio']]);
      } 
      $creados[]=['id'=>$pid,'tienda_id'=>$tid,'total'=>$total]; 
    } 
    $db->commit(); 
    json_response(['success'=>true,'data'=>$creados],201);
  } catch (Exception $e) {
    $db->rollBack();
    json_response(['success'=>false,'message'=>'Error al procesar el pedido: ' . $e->getMessage()], 422);
  }
}
$u=auth();
if($method==='GET'){ $p=[]; $w=''; if($u['rol']==='vendedor'){ $w=' WHERE t.usuario_id=?'; $p[]=(int)$u['id']; } elseif($u['rol']!=='admin') role($u,['admin']); $s=$db->prepare('SELECT p.*,t.nombre tienda FROM pedidos p JOIN tiendas t ON t.id=p.tienda_id'.$w.' ORDER BY p.id DESC'); $s->execute($p); $rows=$s->fetchAll(); foreach($rows as &$r){ $q=$db->prepare('SELECT pi.*,pr.nombre producto FROM pedido_items pi JOIN productos pr ON pr.id=pi.producto_id WHERE pi.pedido_id=?'); $q->execute([(int)$r['id']]); $r['items']=$q->fetchAll(); } json_response(['success'=>true,'data'=>$rows]); }
if($method==='PUT'){ $id=(int)($_GET['id']??0); $d=body(); $estado=$d['estado']??'nuevo'; if(!in_array($estado,['nuevo','confirmado','preparacion','entregado','cancelado'])) json_response(['success'=>false,'message'=>'Estado inválido'],422); if($u['rol']==='vendedor'){ $o=$db->prepare('SELECT p.id FROM pedidos p JOIN tiendas t ON t.id=p.tienda_id WHERE p.id=? AND t.usuario_id=?'); $o->execute([$id,(int)$u['id']]); if(!$o->fetch()) json_response(['success'=>false,'message'=>'No autorizado'],403); } else role($u,['admin']); $s=$db->prepare('UPDATE pedidos SET estado=? WHERE id=?'); $s->execute([$estado,$id]); json_response(['success'=>true]); }
