<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json');

include_once '../config/Database.php';
include_once '../models/Login.php';
include_once '../models/tournament_organizer.php';

$db = new Database;
$conn = $db->connect();

$organizer = new TournamentOrganizer($conn);


/*------TOURNAMENT DETAIL-------*/
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $user = Login::verify(); 
    $organizer->user_id = $user;  
    
    $data = json_decode(file_get_contents("php://input"));

    $result = $organizer->storeTournamentDetail($data);

    if($result['error'] != NULL){

        $status = FALSE;
        $message = 'Fail! Tournament details are not saved.';
        $data = $result['error'];
        $code = 400;
        echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);

    }else{
        
        if($result['tournament_exist'] == 'true'){
            $status = TRUE;  
            $message = 'Fail! Tournament details are already saved between '.$result['start_date'] .' and '.$result['end_date'].' dates.';
            $data = [];
            $code = 400;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        }elseif($result == false){
            $status = FALSE;            
            $message = 'Fail! Tournament details are not saved.';
            $data = [];
            $code = 400;
            echo json_encode(['ResponseCode'=>$code,'ResponseType'=>$status,'ResponseMessage'=>$message,'ResponseData'=>$data],$code);
        }else{
            unset($result['error']);
            unset($result['tournament_exist']);
            $status = TRUE;
            $message = 'Success! Tournament details are saved.';
            $data = $result;
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
/*------TOURNAMENT DETAIL-------*/



?>