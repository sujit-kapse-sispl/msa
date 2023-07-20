<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------GET NO OF COURT DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user; 
    $data = json_decode(file_get_contents("php://input"));  
    $result = $organizer->getNoOfCourtDetail($data);
    
    if(empty($result['data'])){
        $status = FALSE;
        $message = 'No of courts do not found.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }else{
        $status = TRUE;
        $message = 'Success! No of courts are found.';
        $data = $result['data'];
        $code = 200;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    } 
}else{
    $status = FALSE;
    $message = 'Bad Request! Only POST request is allowed.';
    $data = [];
    $code = 405;
    echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);  
}
/*------GET NO OF COURT DETAIL-------*/



?>