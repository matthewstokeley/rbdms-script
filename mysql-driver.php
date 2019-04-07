<?php
//https://gist.github.com/taniarascia/013fb512078329cc6c40f4e4bf98fdbf
class MysqlDriver    {

   private $host;
   private $dbname;
   private $username;
   private $password;
   private $charset;
   private $collate;
   private $pdo;


   /**
    * [__construct description]
    * @param array $options [description]
    */
   function __construct(array $options) {

    $this->host = $options['host'] ? $options['host'] : '127.0.0.1';
		$this->dbname = $options['dbname'] ? $options['dbname'] : 'default-database';
		$this->username = $options['username'] ? $options['username'] : 'database-user';
		$this->password = $options['password'] ? $options['password'] : '';
		$this->charset = $options['charset'] ? $options['charset'] : 'utf8';
		$this->collate = $options['collate'] ? $options['collate'] : 'utf8_unicode_ci';

		try {
			$this->pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname;charset=$this->charset", $this->username, $this->password,
			    array(
			        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			        PDO::ATTR_PERSISTENT => false,
			        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $this->charset COLLATE $this->collate"
			    )
			);

	 } catch (PDOException $e) {
			  print $e->getMessage();
			  die();
		  }
   }

   /**
    * findAll
    * @param  String $table [description]
    * @return Array        [description]
    */
   public function findAll(String $table) {
      $statement = $this->pdo->prepare('SELECT * FROM '.$table);
      $statement->execute();
      return $statement->fetchAll();
   }

   public function findOne(String $table) {
      $statement = $this->pdo->prepare('SELECT * FROM '.$table.' LIMIT 1');
      $statement->execute();
      return $statement->fetch();
   }


   /**
    * Insert row
    * @param  String $name [description]
    * @return Array       [description]
    */
   public function create(String $table, Array $data) {
       
       $row = [
           'name' => $data['name'],
	         'uri' => $data['name']
       ];
       
       $sql = "INSERT INTO ".$table." SET name=:name, uri=:name;";
       $status = $this->pdo->prepare($sql)->execute($row);

       // return $status->fetchAll();
       return $this->findAll($table);
   }

   public function update(String $table, Array $data) {
       $sql = 'UPDATE * WHERE thing = '.$thing.' AND '
   }

   public function findWithGenericJoin() {}

   public function findAllByColumns(String $table, String $columns) {

       $statement = $this->pdo->prepare('SELECT .'$columns'. FROM '.$table);

       $statement->execute();
       return $statement->fetch();

   }

   public function findOneByColumns(String $table, String $columns) {

      $statement = $this->pdo->prepare('SELECT . '$columns'. FROM '.$table.' LIMIT 1');
      $statement->execute();
      return $statement->fetch();

   }

}