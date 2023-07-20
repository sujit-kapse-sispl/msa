<?php 
class Login{

	private $conn;
	private $table='users';
	public $id;
	public $password;
	public $email;

	public function __construct($db){
		$this->conn=$db;
	}
	public function signin(){
		
		$statement = $this->conn->prepare("SELECT salt FROM users WHERE email = (:email) LIMIT 1");
        $statement->bindParam(":email", $this->email, PDO::PARAM_STR);
		$statement->execute();
		if ($statement->rowCount() > 0){

			$row = $statement->fetch();
			$passw_hash = md5(md5($this->password).$row['salt']);
			//return $passw_hash;
			$stmt2 = $this->conn->prepare("SELECT id,firstname,user_type from users  WHERE email = (:email) AND password = (:password) ");
			$stmt2->bindParam(":email",  $this->email, PDO::PARAM_STR);
			$stmt2->bindParam(":password", $passw_hash, PDO::PARAM_STR);
			$stmt2->execute();
			if ($stmt2->rowCount() > 0){
				$row = $stmt2->fetch(PDO::FETCH_ASSOC);
				if($row['user_type'] == 4){
					session_start();
					$_SESSION['user_logged_in'] = 'true';
					$_SESSION['user_id'] = $row['id'];
					return $row;
				}else{
					return 'invalid-type';
				}
			}else{
				return 'invalid-credentials';
			}
			
		}else{
			return 'not-exist';
		}
	}

	public function signout(){

		session_start();
		$_SESSION['user_logged_in'] = '';
		$_SESSION['user_id'] = '';
		session_destroy();
		return 'true';
	}

	public static function verify(){
		session_start();
		if(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] == 'true'){
			return $_SESSION['user_id'];
		}else{
			$status = FALSE;
			$message = 'Please do login for tournament organizer.';
			$data = [];
			$code = 401;
			echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
			exit;
		}
	}
}
?>