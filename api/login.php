<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';

$db = new Database;
$conn = $db->connect();

$login = new Login($conn);


/*------LOGIN-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $data = json_decode(file_get_contents("php://input"));

    $login->email = isset($data->email) ? $data->email : die();
    $login->password = isset($data->password) ? $data->password : die();
    $login_result = $login->signin();
    if($login_result == 'not-exist'){
        $status = FALSE;
        $message = 'User does not found for this email. Please enter correct one.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }elseif($login_result == 'invalid-credentials'){
        $status = FALSE;
        $message = 'Invalid credentials';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }elseif($login_result == 'invalid-type'){
        $status = FALSE;
        $message = 'Only Tournament Organizer user type is allowed.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }else{
        $status = TRUE;
        $message = 'Success! Tournament organizer logged in.';
        $data = $login_result;
        $code = 200;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    } 
    
    
}
/*------LOGIN-------*/





?>