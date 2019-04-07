<?php 

class SQLQueryFactory {

    private $table;
    private $data;
    public  $query;
    public $queryVerbs = [
    	'retrieve' => 'SELECT',
    	'delete' => 'DELETE FROM',
    	'update' => 'UPDATE',
    	'create' => 'INSERT INTO'
    ];

    function __construct() {}

    public function init(string $table, array $data) {
    	$this->setTable($table);
    	$this->setData($data);
    	$this->conditionallyAppendToQuery();
    	return $this;
    }

    private function querySwitch($method) {
    	return $queryVerbs[$method];
    }

    public function getDefaultQuery() {
    	$columns = $this->data['columns'] ? $this->data['columns'] : "*";
    	return 'SELECT '.$columns.' FROM '.$this->table;
    }

    public function conditionallyAppendToQuery() {
    	$queryParameters = [
        'join' => $this->appendJoin(),
    		'where' => $this->appendWhere(),
    		'limit' => $this->appendLimit()
    	];

    	// php maps?
    	$string = $this->getDefaultQuery();

    	foreach ($queryParameters as $key => $value)  {
    		$string = $string ." ".$value;
    	}

    	$this->query = $string;

    	if (!$this->query || !is_string($this->query)) {
    		throw new Exception('The SQL query is improperly formatted.');
    	}

    	return $this;

    }

    public function appendWhere() {
    	return ($this->getData()['column'] && $this->getData()['search_query']) ? 'WHERE '. $this->getData()['column'] .'="'.$this->getData()['search_query'].'"' : '';
    }

    public function appendLimit() {
    	return $this->getData()['limit'] ? " LIMIT ". $this->getData()['limit'] : '';
    }

    public function appendJoin() {
     $data = $this->getData();
     return $data['join_table'] ? ' LEFT JOIN '.$data['join_table'].' ON '.$this->table.'.'.$data['join_column'].'='.$data['join_table'].'.'.$data['join_table_column'] : '';
    }

    public function setTable(string $table) {
    	$this->table = $table;
    	return this;
    }

    public function getTable() {
    	return $this->table;
    }

    public function setData(array $data) {
    	$this->data = $data;
    	return this;
    }
    
    public function getData() {
    	return $this->data;
    }

    public function setQuery(string $query) {
    	$this->query = $query;
    	return $this;
    }

    public function getQuery() {
    	return $this->query;
    }
 
}

class GetParser {

}

class PostParser {

}

class MysqlDriver {

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

   		// inject the query string builder (which we should create a factory for)

   	    $this->queryFactory = new SQLQueryFactory();

	      $this->host = $options['host'] ? $options['host'] : '127.0.0.1';
	     	$this->dbname = $options['dbname'] ? $options['dbname'] : 'default-database';
		    $this->username = $options['username'] ? $options['username'] : 'database-user';
	    	$this->password = $options['password'] ? $options['password'] : '';
    		$this->charset = $options['charset'] ? $options['charset'] : 'utf8';
	    	$this->collate = $options['collate'] ? $options['collate'] : 'utf8_unicode_ci';
		    $this->port = $options['port'] ? $options['port'] : 8888;

		try {

			$this->pdo = new PDO("mysql:host=$this->host;port=8889;dbname=$this->dbname;charset=$this->charset", $this->username, $this->password,
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

   // @todo temporarily add this method to this class
   // @todo string edge cases
   // @todo configuration over convention
   private function parseGetQuery(Array $data) {

        $this->columns = $data['columns'] ? $data['columns'] : "*";

        // if (!$this->columns) {
        // 	throw new Exception('Exception: `Columns` Property Improperly Formatted or Missing');
        // }
       
        $this->column = $data['column'];
	   
        // if (!$this->column) {
        // 	throw new Exception('Exception: `Column` Property Improperly Formatted or Missing');
        // }

	    $this->search_query = $data['search_query'];

        // if (!$this->search_query) {
        // 	throw new Exception('Exception: `Column` Property Improperly Formatted or Missing');
        // }
   		
   		return $this;
   }

   // @todo temporarily add this method to this class 
   private function createSetString(Array $keys) {
   	   
   	   $string = array_reduce($keys, function($prev, $key) {
       	  $delimiter = !empty($prev) ? ', ' : ''; 
          return $prev . $delimiter . $key . "=:" . $key; 
       });

       return $string;
   }

   // @todo temporarily add this method to this class
   private function parseJSONReturnString() {

   }
	
   /**
    * Insert row
    * @param  String $name [description]
    * @return Array       [description]
    */
   public function create(String $table, Array $data) {
       
       $row = $data;
   	   array_shift($row);

       $string = $this->createSetString(array_keys($row));

       $sql = "INSERT INTO ".$table. " SET ". $string;
       $status = $this->pdo->prepare($sql)->execute($row);

       return $status ? json_encode(["status" => 204, 'message' => 'ok']) : json_encode(['status' => 401, 'message' => 'error']);

   }

   public function update(String $table, Array $data) {
   		
   		if (!$data || !is_array($data)) {
   			throw new Exception("Invalid Argument Type");
   		}

   		$row = array();
   		array_shift($row);

   		foreach ($data as $key => $value) {
   		    if ($key !== 'id' && $key !== 'query') {
   				    $row[$key] = $value;
   		    }
   		}

   		if (!$data['id'] || !is_numeric($data['id'])) {
   			throw new Exception('Invalid Argument Type');
   		}

      $string = $this->createSetString(array_keys($row));

   		if (!$data['column']) {
          $data['column'] = 'id';
   		}

   		if (!$data['search_query']) {
   	      $data['search_query'] = $data['id'];
   		}


       $sql = 'UPDATE '.$table.' SET '.$string.' WHERE '.$data['column'].'='.$data['search_query'];

       //echo $string;
       $status = $this->pdo->prepare($sql)->execute($row);
       return $status ? json_encode(["status" => 204, 'message' => 'ok']) : json_encode(['status' => 401, 'message' => 'error']);
   
   }

   /**
     *
     * 
     */
   public function delete(String $table, Array $data) {

   		if (!$data || !is_array($data)) {
   			throw new Exception("Invalid Argument Type");
   		}

   		$row = $data;
   		array_shift($row);

   		if (!$data['id'] || !is_numeric($data['id'])) {
   			throw new Exception('Invalid Argument Type');
   		}

   		if (!$data['column']) {
   			$row['column'] = 'id';
   		}

   		if (!$data['search_query']) {
   			$row['search_query'] = $data['id'];
   		}

       $sql = 'DELETE FROM '.$table.' WHERE '.$row['column'].'='.$row['search_query'];
       $status = $this->pdo->prepare($sql)->execute($row);
       return $status ? json_encode(["status" => 204, 'message' => 'ok']) : json_encode(['status' => 401, 'message' => 'error']);

   }

   public function findAllWithJoin(String $table, Array $data) {
      return $this->fetchAll($table, $data);
   }

   private function prepareQuery(String $table, Array $data) {

   	   try {
	       $this->parseGetQuery($data);
   	   } catch (Exception $e) {
   	   	   print($e->getMessage());
   	   	   die();
   	   }
       
       try {
	       $q = $this->queryFactory->init($table, $data)->getQuery();
       } catch (Exception $e) {
   	   	   print($e->getMessage());
   	   	   die();
   	   }

       try {
	       $statement = $this->pdo->prepare($q);       	
       } catch (Exception $e) {
   	   	   print($e->getMessage());
   	   	   die();
   	   }

       try {
	       $statement->execute();
	   } catch (Exception $e) {
   	   	   print($e->getMessage());
   	   	   die();
	   }

       return $statement;
   }

   private function fetch(String $table, Array $data) {
	   return $this->prepareQuery($table, $data)->fetch();
   }

   private function fetchAll(String $table, Array $data) {
   	   return $this->prepareQuery($table, $data)->fetchAll();
   }

   public function findAll(String $table, Array $data) {
       return $this->fetchAll($table, $data);
   }

   public function findOne(String $table, Array $data) {
       return $this->fetch($table, $data);
   }

}

class API {

	private $path;
	private $length;
	private  $route;
	private  $db;
	
	function __construct() {
		
		$this->db = new MysqlDriver([
			'dbname' => 'world',
			'host' => 'localhost',
			'username' => 'root',
			'password' => 'root',
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci' 
		]);
		
		// set route
		$this->setRoute($this->findRoute());

		// set data
		$this->setData($this->findData());

		return $this;	
	}

	private function findPath() {
		return explode('/', $_SERVER['REQUEST_URI']);
	}

	private function findTableName() {
		// $path = $this->findPath();
		// return $path[3];
	}

	public function findRoute() {
		$path = explode('/', $_SERVER['REQUEST_URI']);
		$length = count($path);
		$route = $path[$length - 1];
		return explode('?', $route)[0];
	}

	private function findData() {
		return $_POST ? $_POST : $_GET; 
	}

	public function setData(Array $data) {
		$this->data = $data;
		return $this;
	}

	public function getData() {
		return $this->data;
	}

	public function setRoute(string $route) {
		$this->route = $route;
		return $this;
	}

	public function getRoute() {
		return $this->route;
	}

	public function switch() {
		$route = $this->getRoute();

		$data = $this->getData();

		// @todo rename parameter
		$method = $this->getData()['query'];

		return $this->db->{$method}($route, $data);
	}

}

try {
	$api = new API();

	// @todo set headers
	header('Content-Type: application/vnd.api+json');
	// @todo
	print_r(json_encode($api->switch()));

} catch (Exception $error) {
	print($error);
}



