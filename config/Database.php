<?php
class Database{
	
    private $host='localhost';
	private $db_name='mysports_sportsbook';
	private $username='root';
	private $password='';
	private $conn;
		
	//public function connect(){
	    
	  //  $this->conn = mysqli_connect($this->host,$this->username,$this->password,$this->db_name);
	  ///  if($this->conn){
	  //      return $this->conn;
	   // }else{
	   //     return false;
	   // }
//	}
	
	public function connect(){
		$this->conn=null;
		try{
			$this->conn=new PDO('mysql:host='.$this->host.';dbname='.$this->db_name,$this->username,$this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			//echo "success";
		}catch(PDOException $e){
			echo "Connection error: ".$e->getMessage();
		}
		return $this->conn;
	}
}
?>