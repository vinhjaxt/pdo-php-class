<?php
/**
 *  DB - A simple database class 
 *
 * @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
 * @git 		https://github.com/indieteq/PHP-MySQL-PDO-Database-Class
 * @version      0.31b
 * Edited by Vinhjaxt
 */
require('Log.class.php');
if(!function_exists('res_ajax')){
	//ajax responser
	/* function res_ajax params @mixed */
	function res_ajax($mix,$force_json=false){
	  while(@ob_end_clean());
	  if($force_json || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')){
		  @header('Content-Type: text/javascript;charset=utf-8');
		  if(!empty($_REQUEST['callback']))
			 echo strip_tags($_REQUEST['callback']),'(',json_encode($mix),')';
		  else
			 echo json_encode($mix);
		exit;
	  }
	  if(!is_string($mix)) $mix=json_encode($mix);
	  if(PHP_SAPI=='cli') exit($mix);
	  echo $mix;
	  exit;
	}//res_ajax
}

if(!function_exists('_redirectTo')){
/*
	Hàm chuyển hướng người dùng đến trang thông báo lỗi.
	@param $url: string url được chuyển đến
	@return: void
*/
function _redirectTo($url){
	@header('Location: '.$url);
	if(in_array($url,array('','.'))) exit('Somethings went wrong.');
	echo '<meta http-equiv="refresh" content="0;URL=\''.$url.'\'" /><script>top.location.href="'.$url.'";</script><script>self.location.href="'.$url.'";</script><script>window.location.href="'.$url.'";</script><script>"use strict";window.location.href="'.$url.'";</script>';
	exit(0);
}//_redirectTo
}//function_exists

if(!function_exists('_response')){
function _response($mixed,$force_json=false){
	if($force_json || isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['_ajax'])){
		@header('Content-Type: text/javascript;charset=utf-8');
		$json=json_encode($mixed);
		if(isset($_GET['callback'])){
			echo strip_tags($_GET['callback']),'(',$json,')';
		}else{
			echo $json;
		}
	  exit;
	}else{
		if(isset($mixed['reload'])){
			if(is_string($mixed['reload']) && $mixed['reload']) _redirectTo($mixed['reload']);
			if(empty($mixed['reload'])) _redirectTo($_SERVER['REQUEST_URI']);
		}
		if(isset($mixed['success'])){
			$status='success';
		}else if(isset($mixed['warn'])){
			$status='warn';
		}else if(isset($mixed['info'])){
			$status='info';
		}else{
			$status='error';
		}
		if(isset($mixed[$status])) $msg=$mixed[$status];
		if(is_array($mixed)) $msg=end($mixed);
		_redirectTo('forms/form-show-msg.php?status='.$status.'&msg='.urlencode($msg).'&code='.$GLOBALS['sign_code']);
	}
	exit(0);
}//_response
}//function_exists

if(!function_exists('_error')){
function _error($msg){
	_response(array('error'=>$msg));
}//_error
}//function_exists

if(!function_exists('send_error')){
	function send_error($error,$force_json=false){
		if($force_json || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')){
			res_ajax(array('error'=>$error));
			exit();
		}
		if(!is_string($error)) $error=json_encode($error);
		if(PHP_SAPI=='cli') exit($error);
		$protocol = 'HTTP/1.0';
		if(isset($_SERVER['SERVER_PROTOCOL']) && 'HTTP/1.1' == $_SERVER['SERVER_PROTOCOL']) $protocol = 'HTTP/1.1';
		@header("$protocol 503 $error", true, 503 );
		@header('Status: 503 '.$error);
		@header('X-Status: 503 '.$error);
		@header('Retry-After: 300');
		exit($error);
	}//send_error
}//function_exists

if(!class_exists('db')){
	class db
	{
		# @object, The PHP Data Object
		public $pdo;
		
		# @object, PDO statement object
		private $stmt;
		
		# @str,  The database settings
		private $dsn='';
		private $user='';
		private $password='';
		
		# @bool ,  Connected to the database
		private $bConnected = false;
		private $reConnect = false;
		
		# @object, Object for logging exceptions	
		private $log;
		
		# @array, The parameters of the SQL query
		private $parameters;
		
		# @boolean, is transaction?
		private $transaction=false;
		
		/**
		 *   Default Constructor 
		 *
		 *	1. Instantiate Log class.
		 *	2. Connect to database.
		 *	3. Creates the parameter array.
		 */
		public function __construct($db_dsn='', $db_user='', $db_password='')
		{
			if(empty($db_dsn)){
				if(isset($GLOBALS['db_info']) && is_array($GLOBALS['db_info'])){
					/*
					Database settings
					*/
					$db_user=$GLOBALS['db_info']['user'];
					$db_password=$GLOBALS['db_info']['pass'];
					$db_dsn = 'mysql:dbname='.$GLOBALS['db_info']['dbname'].';host='.(isset($GLOBALS['db_info']['host'])?$GLOBALS['db_info']['host']:'localhost').';port='.(isset($GLOBALS['db_info']['port'])?$GLOBALS['db_info']['port']:'3306');
					if(isset($GLOBALS['db_info']['dsn'])) $db_dsn=$GLOBALS['db_info']['dsn'];
				}
			}
			if(empty($db_dsn)) include('settings.ini.php');
			$this->dsn=$db_dsn;
			$this->user=$db_user;
			$this->password=$db_password;
			if(!$this->log) $this->log = new Log();
			$this->connect();
			$this->parameters = array();
		}
		public function __destruct()
		 {
			$this->close();
			$GLOBALS['db_info']=array('user'=>$this->user,'pass'=>$this->password,'dsn'=>$this->dsn);
	/*
			$this->dsn='';
			$this->user='';
			$this->password='';
			$this->bConnected = false;
	*/
		 }
		
		/**
		 *	This method makes connection to the database.
		 *	
		 *	1. Reads the database settings from a ini file. 
		 *	2. Puts  the ini content into the settings array.
		 *	3. Tries to connect to the database.
		 *	4. If connection failed, exception is displayed and a log file gets created.
		 */
		private function connect()
		{
			if($this->bConnected) return true;
			try {
				$this->pdo = new PDO($this->dsn/*;charset=utf8mb4*/, $this->user, $this->password, array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' // SET CHARACTER SET utf8
				));
				
				# We can now log any exceptions on Fatal error. 
				$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				# Disable emulation of prepared statements, use REAL prepared statements instead.
				$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
				# Connection succeeded, set the boolean to true.
				$this->bConnected = true;
			}
			catch (PDOException $e) {
				$this->bConnected = false;
				# Write into log
				$exception = $this->ExceptionLog($e->getMessage()."\n".$this->dsn."\n".$this->user.'@'.$this->password);
				   $this->reConnect=true;
				if($this->reConnect){
				   send_error('Cant connect to database. See the Log file.');
				   die();
				}
	/* else{
				   $this->reConnect=true;
				   $this->dsn='sqlite:database.sqlite';
				   $this->user='';
				   $this->password='';
				   $this->connect();
				}
	*/
			}
		}
		/*
		 *   You can use this little method if you want to close the PDO connection
		 *
		 */
		public function close()
		{
			# Set the PDO object to null to close the connection
			# http://www.php.net/manual/en/pdo.connections.php
	//        $this->stmt->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
			$this->stmt = null;
			$this->pdo = null;
			$this->bConnected = false;
		}
		
		/**
		 *	Every method which needs to execute a SQL query uses this method.
		 *	
		 *	1. If not connected, connect to the database.
		 *	2. Prepare Query.
		 *	3. Parameterize Query.
		 *	4. Execute Query.	
		 *	5. On exception : Write Exception into the log + SQL query.
		 *	6. Reset the Parameters.
		 */
		private function Init($query, $parameters = '')
		{
			# Connect to database
			if (!$this->bConnected) {
				$this->connect();
			}
			try {
				# Prepare query
				$this->stmt = $this->pdo->prepare($query);
				
				# Add parameters to the parameter array	
				$this->parameters=$parameters;
				
				# Bind parameters
				if (!empty($parameters)){
					if(!is_array($parameters)) $parameters=array(1=>$parameters);
					foreach($parameters as $param => $value){
						switch (true){
							case is_int($value):
								$type = PDO::PARAM_INT;
								break;
							case is_bool($value):
								$type = PDO::PARAM_BOOL;
								break;
							case is_null($value):
								$type = PDO::PARAM_NULL;
								break;
							default:
								$type = PDO::PARAM_STR;
								break;
						}
						// Add type when binding the values to the column
						if(!is_numeric($param)) $param=':'.$param;
						$this->stmt->bindValue($param, $value, $type);
					}
				}
				
				# Execute SQL 
				$res=$this->stmt->execute();
				if(!$res){
					send_error($this->ExceptionLog('Cant init query.', $query));
					die();
				}
			}
			catch (PDOException $e) {
				# Write into log and display Exception
				send_error($this->ExceptionLog($e->getMessage(), $query));
				die();
			}
			
			# Reset the parameters
			$this->parameters = array();
			return $res;
		}
		
		/**
		 *  If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
		 *	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows
		 *
		 *  @param  string $query
		 *	@param  array  $params
		 *	@param  int    $fetchmode
		 *	@return mixed
		 */
		public function query($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
		{
			
			if(!($this->Init($query, $params))){
				send_error($this->ExceptionLog('Cant init query.', $query));
				die();
			}
			
			if($this->transaction===false){
				$query = trim(str_replace("\r", ' ', $query));
				$rawStatement = explode(' ', preg_replace("/\s+|\t+|\n+/", ' ', trim($query)));
				
				# Which SQL statement is used 
				$statement = strtolower($rawStatement[0]);
				
				$res=NULL;
				if ($statement === 'select' || $statement === 'show'){
					$res=$this->stmt->fetchAll($fetchmode);
				} elseif ($statement === 'update' || $statement === 'delete'){
					$res=$this->stmt->rowCount();
				} elseif ($statement === 'insert'){
					$res=$this->pdo->lastInsertId();
				}
				$this->stmt->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
			}else{
				$res=true;
			}
			return $res;
		}
		
		/**
		 *  Returns the last inserted id.
		 *  @return string
		 */
		public function lastInsertId()
		{
			return $this->pdo->lastInsertId();
		}
		
		/**
		 * Starts the transaction
		 * @return boolean, true on success or false on failure
		 */
		public function beginTransaction()
		{
			$this->transaction=true;
			return $this->pdo->beginTransaction();
		}
		
		/**
		 *  Execute Transaction
		 *  @return boolean, true on success or false on failure
		 */
		public function executeTransaction()
		{
			return $this->pdo->commit();
		}
		
		/**
		 *  Rollback of Transaction
		 *  @return boolean, true on success or false on failure
		 */
		public function rollBack()
		{
			$this->transaction=false;
			return $this->pdo->rollBack();
		}
		
		/**
		 *	Returns an array which represents a column from the result set 
		 *
		 *	@param  string $query
		 *	@param  array  $params
		 *	@return array
		 */
		public function column($query, $params = null)
		{
			$this->Init($query, $params);
			$Columns = $this->stmt->fetchAll(PDO::FETCH_NUM);
			
			$column = null;
			
			foreach ($Columns as $cells) {
				$column[] = $cells[0];
			}
			$this->stmt->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
			return $column;
			
		}
		/**
		 *	Returns an array which represents a row from the result set 
		 *
		 *	@param  string $query
		 *	@param  array  $params
		 *   	@param  int    $fetchmode
		 *	@return array
		 */
		public function row($query, $params = null, $fetchmode = PDO::FETCH_ASSOC)
		{
			$this->Init($query, $params);
			$result = $this->stmt->fetch($fetchmode);
			$this->stmt->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
			return $result;
		}
		/**
		 *	Returns the value of one single field/column
		 *
		 *	@param  string $query
		 *	@param  array  $params
		 *	@return string
		 */
		public function single($query, $params = null)
		{
			$this->Init($query, $params);
			$result = $this->stmt->fetchColumn();
			$this->stmt->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
			return $result;
		}
		/**	
		 * Writes the log and returns the exception
		 *
		 * @param  string $message
		 * @param  string $sql
		 * @return string
		 */
		private function ExceptionLog($message, $sql = '')
		{
			$exception = 'Unhandled Exception. <br />'."\r\n";
			$exception .= $message."\r\n";
			$exception .= '<br /> You can find the error back in the log.'."\r\n";
			
			if (!empty($sql)) {
				# Add the Raw SQL to the Log
				$message .= "\r\nRaw SQL : ".$sql."\r\n";
			}
			if (!empty($this->parameters)) {
				# Add the Parameters to the Log
				$message .= 'with Parameters : '.json_encode($this->parameters)."\r\n";
			}
			# Write into log
			$this->log->write($message."\r\n");
			
			//return $exception;
			return 'Unhandled Exception in your SQL query.';
		}
		
		/*
		If call undefined method, check the pdo
		*/
	   public function __call($method, $args) {
		   //$args=func_get_args();
		   if(method_exists($this, $method)){
			   /*
				$callable = array($this, $method);
				call_user_func_array($callable, $args);
				*/
				return $this->{$method}($args[0],$args[1],$args[2]);
		   }else{
			   $callable = array($this->pdo, $method);
			   if(is_callable($callable)){
				   return call_user_func_array($callable, $args);
			   }else exit('Undefined method of db class: '.$method);
		   }
	   }
	   
	   /*
		Get the PDO
	   */
	   public function getPDO(){
		   return $this->pdo;
	   }

	   
	   # Update row
	   # @table string table name
	   # @params array cloumns to be update
	   # @where (optional) array condition to update
	   public function update($table,$params,$where=array()){
		   if(!is_array($params) || !is_array($where)) return false;

		   $query='update `'.$table.'` set ';

		   $columns=array();
		   $parameters=array();
		   foreach($params as $k=>$v){
			   $columns[]='`'.$k.'` = :p1'.$k;
			   $parameters['p1'.$k]=$v;
		   }
		   $query.=implode(',',$columns);
		   $columns=array();
		   
		   if(!empty($where)){
			   foreach($where as $k=>$v){
				   $columns[]='`'.$k.'` = :p2'.$k;
				   $parameters['p2'.$k]=$v;
			   }
			   $query.=' where '.implode(' and ',$columns);
			   $columns=array();
		   }
		   
		   return $this->query($query,$parameters);
	   }
	   
	   # Insert row into table
	   # @table string table name
	   # @params array, keys to be used as columns
	   public function insert($table,$params){
		   $query='insert into `'.$table.'` set ';
		   $columns=array();
		   $parameters=array();
		   foreach($params as $k=>$v){
			   $columns[]='`'.$k.'` = :p1'.$k;
			   $parameters['p1'.$k]=$v;
		   }
		   $query.=implode(',',$columns);
		   $columns=array();
		   return $this->query($query,$parameters);
	   }
	   
	   # Delete row from table
	   # @table string table name
	   # @where array conditions
	   public function delete($table,$where=array()){
		   $query='delete from `'.$table.'`';
		   $columns=array();
		   $parameters=array();
		   if(!empty($where)){
			   foreach($where as $k=>$v){
				   $columns[]='`'.$k.'` = :p2'.$k;
				   $parameters['p2'.$k]=$v;
			   }
			   $query.=' where '.implode(' and ',$columns);
			   $columns=array();
		   }
		   return $this->query($query,$parameters);
	   }
	}//end of class

}//class_exists
