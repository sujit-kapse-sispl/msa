<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------STORE TOURNAMENT GAME RULE DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user;  
    
    $data = json_decode(file_get_contents("php://input"));

    $result = $organizer->storeTournamentGameRuleDetail($data);

    if($result['error'] != NULL){

        $status = FALSE;    
        $message = 'Fail! Tournament game rule details are not saved.';
        $data = $result['error'];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);

    }else{
        
        if(empty($result['data'])){
            $status = FALSE;
            $message = 'Fail! Tournament game rule details are not saved.';
            $data = [];
            $code = 400;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        }elseif($result['data'] == 'exist'){
            unset($result['error']);
            unset($result['data']);
            $status = TRUE;
            $message = 'fail! Tournament game rule details are already saved for this tournament id.';
            $data = [];
            $code = 200;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        }else{
            unset($result['error']);
            $status = TRUE;
            $message = 'Success! Tournament game rule details are saved.';
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
/*------STORE TOURNAMENT GAME RULE DETAIL-------*/



?>