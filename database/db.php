<?php 
	
	include_once '../config/config.php';

	class Connection
    {	

    	private $host = DB_HOST;
		private $username = DB_USER;
		private $password = DB_PASS;
		private $dbname = DB_NAME;

        public $mysqli;
        
        public function __construct() { 

            $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->dbname);

			if($this->mysqli->connect_error){
				die("Connection failed: " . $this->mysqli->connet_error);
			}
        }       
    }

?>


