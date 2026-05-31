<?php
class Database {
  private $host = 'TU_HOST_AQUI'; // Ej: localhost o mysql.hostinger.com
  private $db_name = 'TU_BASE_DATOS_AQUI';
  private $username = 'TU_USUARIO_AQUI';
  private $password = 'TU_CONTRASEÑA_AQUI';
  public function connect() {
    try {
      return new PDO(
        "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
        $this->username,
        $this->password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
      );
    } catch(PDOException $e) {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(500);
      echo json_encode(['success'=>false,'message'=>'Error DB','error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }
}
