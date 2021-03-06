<?php

namespace App\DAO\db_reservas_locais;

abstract class Conexao{
  /*
  * @var \PDO
  */
  protected $pdo;

  public function __construct(){
    try{

      $host = getenv('DB_RESERVA_LOCAIS_MYSQL_HOST');
      $port = getenv('DB_RESERVA_LOCAIS_MYSQL_PORT');
      $user = getenv('DB_RESERVA_LOCAIS_MYSQL_USER');
      $pass = getenv('DB_RESERVA_LOCAIS_MYSQL_PASSWORD');
      $dbname = getenv('DB_RESERVA_LOCAIS_MYSQL_DBNAME');

      $dsn = "mysql:host={$host};dbname={$dbname};port={$port};";
      $this->pdo = new \PDO($dsn,$user,$pass);
      $this->pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
    }catch(Exception $e){
      echo 'Erro ao conectar ao banco de dados';
    }
  }
}