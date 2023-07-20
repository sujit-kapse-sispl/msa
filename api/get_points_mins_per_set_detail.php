<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------POINT MINUTES PER SET DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user;  
    
    $data = json_decode(file_get_contents("php://input"));
    $result = $organizer->getPointPerMinuteSetDetail($data);
    
    if($result['error'] != NULL){

        $status = FALSE;
        $message = 'Fail! Point per minute set are not found.';
        $data = $result['error'];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);

    }elseif(empty($result['data'])){
        unset($result['error']);
        $status = FALSE;
        $message = 'Point per minute set does not exist.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }else{
        unset($result['error']);
        $status = TRUE;
        $message = 'Success! Point per minute set details are found.';
        $data = $result['data'];
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
/*------POINT MINUTES PER SET DETAIL-------*/



?>