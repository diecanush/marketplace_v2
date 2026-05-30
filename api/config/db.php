<?php
class Database {
  private $host = 'localhost';
  private $db_name = 'marketplace';
  private $username = 'root';
  private $password = '';
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
