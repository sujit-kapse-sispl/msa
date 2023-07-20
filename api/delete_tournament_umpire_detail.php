<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------DELETE TOURNAMENT UMPIRE DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user;  
    
    $data = json_decode(file_get_contents("php://input"));

    $result = $organizer->deleteTournamentUmpireDetail($data);

    if($result['error'] != NULL){

        $status = FALSE;    
        $message = 'Fail! Tournament umpire details are not found.';
        $data = $result['error'];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);

    }else{
        
        if(empty($result['data'])){
            $status = FALSE;
            $message = 'Fail! Tournament umpire details are not found.';
            $data = [];
            $code = 400;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        }else{
            unset($result['error']);
            $status = TRUE;
            $message = 'Success! Tournament umpire details are deleted.';
            $data = $result['data'];
            $code = 200;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        } 
    }
    
    
}else{
    $status = FALSE;
    $message = 'Bad Request! Only POST request is allowed.';
    $data = [];
    $code = 405;
    echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);  
}
/*------DELETE TOURNAMENT UMPIRE DETAIL-------*/



?>