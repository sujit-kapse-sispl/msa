<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------ORGANIZER DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'GET'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user;   
    $result = $organizer->getOrganizerDetail();
    
    if(empty($result['data'])){
        $status = FALSE;
        $message = 'Tournament organizer does not exist.';
        $data = [];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
    }else{
        $status = TRUE;
        $message = 'Success! Tournament organizer details are found.';
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
/*------ORGANIZER DETAIL-------*/



?>