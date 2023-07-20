<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';

$db = new Database;
$conn = $db->connect();

$login = new Login($conn);



/*------LOGOUT-------*/
if($_SERVER['REQUEST_METHOD'] == 'GET'){

    $login_result = $login->signout();
    
    if($login_result != 'true'){
        $status = FALSE;
        $message = 'Fail! Tournament organizer does not logged out.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }else{
        $status = TRUE;
        $message = 'Success! Tournament organizer logged out.';
        $data = [];
        $code = 200;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    } 
}else{
    $status = FALSE;
    $message = 'Bad Request! Only GET request is allowed.';
    $data = [];
    $code = 405;
    echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);  
}
/*------LOGOUT-------*/

?>