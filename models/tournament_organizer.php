<?php 
class TournamentOrganizer{

	private $conn;
	private $table='users';
	public $id;
	public $user_id;
	public $password;
	public $email;
	public $mysqli_conn;

	public function __construct($db){
		$this->conn=$db;
		$this->mysqli_conn = mysqli_connect('localhost','root','','mysports_sportsbook');
	}

	public function validate($data){

		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		$data = strip_tags($data);
		return $data;

	}

	/*-------------------------GET ORGANIZER DETAIL------------------------------ */
	public function getOrganizerDetail(){

		$stmt = $this->conn->prepare("SELECT users.id,CONCAT(users.firstname,' ',users.lastname) as fullname,users.email,phones.phone_no FROM users LEFT JOIN phones ON users.id=phones.users_id WHERE users.id = (:id)");
        $stmt->bindParam(":id", $this->user_id, PDO::PARAM_INT);
		$stmt->execute();
		$arr= [];
		if ($stmt->rowCount() > 0){
		    $row = $stmt->fetch(PDO::FETCH_ASSOC);
			return ['data'=>$row];
		}else{
			return ['data'=>''];
		}
	}
	/*-------------------------GET ORGANIZER DETAIL------------------------------ */


	/*-------------------------STORE TOURNAMENT DETAIL------------------------------ */
	public function storeTournamentDetail($data){
		
		$error = new ArrayObject();

		if(!isset($data->sports_type) || $data->sports_type == NULL){
			$error->append('Please enter sport type.');
		}
		
		if(!isset($data->tournament_name) || $data->tournament_name == NULL){
			$error->append('Please enter tournament name.');
		}

		if(!isset($data->management_name) || $data->management_name == NULL){
			$error->append('Please enter management name.');
		}

		if(!isset($data->t_start_date) || $data->t_start_date == NULL){
			$error->append('Please enter tournament start date.');
		}

		if(!isset($data->t_end_date) || $data->t_end_date == NULL){
			$error->append('Please enter tournament end date.');
		}
		
		if(!isset($data->entry_start_date) || $data->entry_start_date == NULL){
			$error->append('Please enter registration start date.');
		} 

		if(!isset($data->entry_end_date) || $data->entry_end_date == NULL){
			$error->append('Please enter registration end date.');
		}

		if(!isset($data->entry_close_time) || $data->entry_close_time == NULL){
			$error->append('Please enter registration close time.');
		}

		if(!isset($data->is_withdrawable) || $data->is_withdrawable == NULL){
			$error->append('Please select withdraw option.');
		}elseif(isset($data->is_withdrawable) && $data->is_withdrawable == 'yes' && !isset($data->withdraw_reason)){
			$error->append('Please enter withdraw reason.');
		}elseif($data->is_withdrawable == 'yes' && $data->withdraw_reason == NULL){
			$error->append('Please enter withdraw reason.');
		}
		
		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$arr = [
				'sports_type'=>$this->validate($data->sports_type),
				'tournament_name'=>$this->validate($data->tournament_name),
				'management_name'=>$this->validate($data->management_name),
				't_start_date'=>$this->validate($data->t_start_date),
				't_end_date'=>$this->validate($data->t_end_date),
				'entry_start_date'=>$this->validate($data->entry_start_date),
				'entry_end_date'=>$this->validate($data->entry_end_date),
				'entry_close_time'=>$this->validate($data->entry_close_time),
				'is_withdrawable'=>$this->validate($data->is_withdrawable),
				'user_id'=>$this->validate($data->user_id)
			];
			$arr['withdraw_reason'] = '';
			if(isset($data->withdraw_reason) && $data->withdraw_reason != NULL){
				$arr['withdraw_reason'] = $this->validate($data->withdraw_reason);
			}			
			//check tournament exist of not 			
			$sql = 'SELECT * FROM tournament_details WHERE (t_start_date <= "'.$arr['t_start_date'].'" and t_end_date >= "'.$arr['t_end_date'].'" )  OR ("'.$arr['t_start_date'].'" between t_start_date and t_end_date) OR ("'.$arr['t_end_date'].'" between t_start_date and t_end_date) OR (t_start_date >= "'.$arr['t_start_date'].'" and t_end_date <= "'.$arr['t_end_date'].'")';	
			$result = mysqli_query($this->mysqli_conn,$sql);
			$no_rows = mysqli_num_rows($result);
			if ($no_rows > 0){
				return ['error'=>NULL,'tournament_exist'=>'true','start_date'=>$arr['t_start_date'],'end_date'=>$arr['t_end_date']];
			}else{
				$is_delete = 0;
				$stmt = $this->conn->prepare("INSERT INTO tournament_details(sports_type,tournament_name,management_name,t_start_date,t_end_date,entry_start_date,entry_end_date,entry_close_time,is_withdrawable,users_id,withdraw_reason,is_delete) values((:sports_type),(:tournament_name),(:management_name),(:t_start_date),(:t_end_date),(:entry_start_date),(:entry_end_date),(:entry_close_time),(:is_withdrawable),(:user_id),(:withdraw_reason),(:is_delete))");
				$stmt->bindParam(":sports_type", $arr['sports_type'], PDO::PARAM_INT);
				$stmt->bindParam(":tournament_name", $arr['tournament_name'], PDO::PARAM_STR);
				$stmt->bindParam(":management_name", $arr['management_name'], PDO::PARAM_STR);
				$stmt->bindParam(":t_start_date", $arr['t_start_date'], PDO::PARAM_STR);
				$stmt->bindParam(":t_end_date", $arr['t_end_date'], PDO::PARAM_STR);
				$stmt->bindParam(":entry_start_date", $arr['entry_start_date'], PDO::PARAM_STR);
				$stmt->bindParam(":entry_end_date", $arr['entry_end_date'], PDO::PARAM_STR);
				$stmt->bindParam(":entry_close_time", $arr['entry_close_time'], PDO::PARAM_STR);
				$stmt->bindParam(":is_withdrawable", $arr['is_withdrawable'], PDO::PARAM_STR);
				$stmt->bindParam(":withdraw_reason", $arr['withdraw_reason'], PDO::PARAM_STR);
				$stmt->bindParam(":user_id", $arr['user_id'], PDO::PARAM_INT);
				$stmt->bindParam(":is_delete",$is_delete, PDO::PARAM_INT);
				if($stmt->execute()){
					$id = $this->conn->lastInsertId();
					return ['error'=>NULL,'tournament_id'=>$id,'tournament_exist'=>'false'];
				}else{
					return false;
				}
			}
			
			
		}

	}
	/*-------------------------STORE TOURNAMENT DETAIL------------------------------ */


	/*-----------------------GET TOURNAMENT DETAIL----------------------------------- */
	public function getTournamentDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_details.id,game_types_master.name as sport_type_name,tournament_details.tournament_name,tournament_details.management_name,tournament_details.t_start_date,tournament_details.t_end_date,tournament_details.entry_start_date,tournament_details.entry_end_date,tournament_details.entry_close_time,tournament_details.is_withdrawable,tournament_details.withdraw_reason,tournament_details.image,CONCAT(users.firstname,' ',users.lastname) as tournament_user_name FROM tournament_details 
										LEFT JOIN game_types_master ON  game_types_master.id = tournament_details.sports_type
										LEFT JOIN users ON  users.id = tournament_details.users_id
										WHERE tournament_details.id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);					
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------GET TOURNAMENT DETAIL------------------------------------- */


	/*-------------------------STORE TOURNAMENT KNOCKOUT DETAIL------------------------ */
	public function storeTournamentKnockoutDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->match_type_id) || count($data->match_type_id) == 0){
			$error->append('Please enter match type.');
		}
		
		if(!isset($data->point_type_id) || count($data->point_type_id) == 0){
			$error->append('Please enter point type.');
		}

		if(!isset($data->set_type_id) || count($data->set_type_id) == 0){
			$error->append('Please enter no. of sets.');
		}

		if(!isset($data->points_id) || count($data->points_id) == 0){
			$error->append('Please enter points .');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			//check tournament id is exist or not in tournament knockout
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_knockouts WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute(); 
			$knockout_arr = [];      

			if ($check_stmt->rowCount() == 0){
				//insert tournament knockout details with respect to tournament id
				
				for($i=0;$i<4;$i++){

					$arr = [
						'tournament_id'=>$this->validate($data->tournament_id),
						'match_type_id'=>$this->validate($data->match_type_id[$i]),
						'point_type_id'=>$this->validate($data->point_type_id[$i]),
						'set_type_id'=>$this->validate($data->set_type_id[$i]),
						'points_id'=>$this->validate($data->points_id[$i]),
					];
						
					$stmt = $this->conn->prepare("INSERT INTO tournament_knockouts(tournament_details_id,match_type_id,point_type_id,set_type_id,points_id) VALUES ((:tournament_id),(:match_type_id),(:point_type_id),(:set_type_id),(:points_id))");
					$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":match_type_id",$arr['match_type_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":point_type_id",$arr['point_type_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":set_type_id",$arr['set_type_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":points_id",$arr['points_id'] , PDO::PARAM_INT);
					if($stmt->execute()){
						$id = $this->conn->lastInsertId();
						array_push($knockout_arr,$id);
					}					

				}
				if(count($knockout_arr) > 0){
					return ['error'=>NULL,'tournament_knockout_id'=>$knockout_arr];
				}else{
					return ['error'=>NULL,'tournament_knockout_id'=>FALSE];
				}
			}else{
				return ['error'=>NULL,'tournament_knockout_id'=>'exist'];
			}
			

		}
	
	}
	/*-------------------------STORE TOURNAMENT KNOCKOUT DETAIL------------------------ */


	/*-------------------------GET TOURNAMENT KNOCKOUT ID DETAIL------------------------ */
	public function getTournamentKnockoutIdDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_knockout_id) || $data->tournament_knockout_id == NULL){
			$error->append('Please enter tournament knockout id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_knockout_id = $this->validate($data->tournament_knockout_id);
			$stmt = $this->conn->prepare("SELECT tournament_knockouts.id,tournament_details.tournament_name,match_types_master.name as match_type_name,point_types.name as point_type_name,set_types.name as set_type_name,points_mins_per_set.points,points_mins_per_set.mins FROM tournament_knockouts 
										LEFT JOIN tournament_details ON  tournament_knockouts.tournament_details_id = tournament_details.id
										LEFT JOIN match_types_master ON  tournament_knockouts.match_type_id = match_types_master.id 
										LEFT JOIN point_types ON  tournament_knockouts.point_type_id = point_types.id
										LEFT JOIN set_types ON  tournament_knockouts.set_type_id = set_types.id
										LEFT JOIN points_mins_per_set ON  tournament_knockouts.points_id = points_mins_per_set.id
										WHERE tournament_knockouts.id = (:tournament_knockout_id)");
			$stmt->bindParam(":tournament_knockout_id",$tournament_knockout_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}

	}
	/*-------------------------GET TOURNAMENT KNOCKOUT ID DETAIL---------------------------------------- */


	/*-------------------------GET TOURNAMENT KNOCKOUT DETAIL---------------------------------------- */
	public function getTournamentKnockoutDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_knockouts.id,tournament_details.tournament_name,match_types_master.name as match_type_name,point_types.name as point_type_name,set_types.name as set_type_name,points_mins_per_set.points,points_mins_per_set.mins FROM tournament_knockouts 
										LEFT JOIN tournament_details ON  tournament_knockouts.tournament_details_id = tournament_details.id
										LEFT JOIN match_types_master ON  tournament_knockouts.match_type_id = match_types_master.id 
										LEFT JOIN point_types ON  tournament_knockouts.point_type_id = point_types.id
										LEFT JOIN set_types ON  tournament_knockouts.set_type_id = set_types.id
										LEFT JOIN points_mins_per_set ON  tournament_knockouts.points_id = points_mins_per_set.id
										WHERE tournament_knockouts.tournament_details_id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();		
			if ($stmt->rowCount() > 0){
				$arr = [];
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){					
					array_push($arr,$row);
				}
				return ['error'=>NULL,'data'=>$arr];			
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}

	}
	/*-------------------------GET TOURNAMENT KNOCKOUT DETAIL---------------------------------------- */


	/*-------------------------GET MATCH TYPE DETAIL----------------------------------- */
	public function getMatchTypeDetail(){

		$stmt = $this->conn->prepare("SELECT * FROM match_types_master");
		$stmt->execute();
		$arr = [];
		if ($stmt->rowCount() > 0){
			
		    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				array_push($arr,$row);
			}
			if(count($arr) == 0){
				return ['data'=>''];
			}else{
				return ['data'=>$arr];
			}
			
		}else{
			return ['data'=>''];
		}
	}
	/*-------------------------GET MATCH TYPE DETAIL-------------------------------------- */


	/*-------------------------GET SET TYPE DETAIL----------------------------------- */
	public function getSetTypeDetail(){

		$stmt = $this->conn->prepare("SELECT * FROM set_types");
		$stmt->execute();
		$arr = [];
		if ($stmt->rowCount() > 0){
			
		    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				array_push($arr,$row);
			}
			if(count($arr) == 0){
				return ['data'=>''];
			}else{
				return ['data'=>$arr];
			}
			
		}else{
			return ['data'=>''];
		}
	}
	/*-------------------------GET SET TYPE DETAIL-------------------------------------- */


	/*-------------------------GET POINT TYPE DETAIL----------------------------------- */
	public function getPointTypeDetail(){

		$stmt = $this->conn->prepare("SELECT * FROM point_types");
		$stmt->execute();
		$arr = [];
		
		if ($stmt->rowCount() > 0){
			
		    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				array_push($arr,$row);
			}
			if(count($arr) > 0){
				return ['data'=>$arr];
			}else{
				return ['data'=>''];
			}
			
		}else{
			return ['data'=>''];
		}
	}
	/*-------------------------GET POINT TYPE DETAIL-------------------------------------- */


	/*-------------------------GET POINT PER MINUTE SET DETAIL---------------------------- */
	public function getPointPerMinuteSetDetail($data){

		$error = new ArrayObject();
		if(!isset($data->set_type_id) || $data->set_type_id == NULL){
			$error->append('Please enter set type id.');
		}

		if(!isset($data->point_type_id) || $data->point_type_id == NULL){
			$error->append('Please enter point type id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error,'data'=>''];
		}else{

			$arr = [
				'point_type_id'=>$this->validate($data->point_type_id),
				'set_type_id'=>$this->validate($data->set_type_id)
			];
			$stmt = $this->conn->prepare("SELECT * FROM points_mins_per_set WHERE set_types_id=(:set_type_id) AND point_types_id=(:point_type_id)");
			$stmt->bindParam(":point_type_id",$arr['point_type_id'] , PDO::PARAM_INT);
			$stmt->bindParam(":set_type_id",$arr['set_type_id'] , PDO::PARAM_INT);
			$stmt->execute();
			$arr = [];
			if ($stmt->rowCount() > 0){
				
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					array_push($arr,$row);
				}
				if(count($arr) == 0){
					return ['error'=>NULL,'data'=>''];
				}else{
					return ['error'=>NULL,'data'=>$arr];
				}
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-------------------------GET POINT PER MINUTE SET DETAIL-------------------------------------- */	
	

	/*-------------------------GET COUNTRY DETAIL---------------------------------------------------- */
	public function getCountryDetail(){

		$stmt = $this->conn->prepare("SELECT countries_id,country_name FROM countries");
		$stmt->execute();
		$arr = [];
		if ($stmt->rowCount() > 0){

		    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				array_push($arr,$row);
			}
			if(count($arr) == 0){
				return ['error'=>NULL,'data'=>$arr];
			}else{
				return ['error'=>NULL,'data'=>$arr];
			}
			
		}else{
			return ['error'=>NULL,'data'=>$arr];
		}
	}
	/*-------------------------GET COUNTRY DETAIL---------------------------------------------------- */


	/*-------------------------GET STATE DETAIL AS PER COUNTRY--------------------------------------*/
	public function getCountryStateDetail($data){

		$error = new ArrayObject();
		if(!isset($data->country_id) || $data->country_id == NULL){
			$error->append('Please enter country id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$arr = [];
			$country_id = $this->validate($data->country_id);
			$stmt = $this->conn->prepare("SELECT states_id,state_name FROM states WHERE countries_id=(:country_id)");
			$stmt->bindParam(":country_id",$country_id , PDO::PARAM_INT);
			$stmt->execute();
		
			if ($stmt->rowCount() > 0){
				
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					array_push($arr,$row);
				}
				if(count($arr) == 0){
					return ['error'=>NULL,'data'=>$arr];
				}else{
					return ['error'=>NULL,'data'=>$arr];
				}
				
			}else{
				return ['error'=>NULL,'data'=>$arr];
			}
		}
	}
	/*-------------------------GET STATE DETAIL AS PER COUNTRY--------------------------------------*/


	/*-----------------------STORE TOURNAMENT VENUE DETAIL------------------------------------------ */
	public function storeTournamentVenueDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->venue_name) || $data->venue_name == NULL){
			$error->append('Please enter venue name.');
		}

		if(!isset($data->country) || $data->country == NULL){
			$error->append('Please enter country.');
		}

		if(!isset($data->state) || $data->state == NULL){
			$error->append('Please enter state.');
		}

		if(!isset($data->city) || $data->city == NULL){
			$error->append('Please enter city.');
		}

		if(!isset($data->no_courts) || $data->no_courts == NULL){
			$error->append('Please enter no. of courts.');
		}

		if(!isset($data->start_date) || $data->start_date == NULL){
			$error->append('Please enter tournament start date.');
		}

		if(!isset($data->end_date) || $data->end_date == NULL){
			$error->append('Please enter tournament end date.');
		}

		if(!isset($data->start_time) || $data->start_time == NULL){
			$error->append('Please enter tournament start time.');
		}

		if(!isset($data->end_time) || $data->end_time == NULL){
			$error->append('Please enter tournament end time.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error,'data'=>''];
		}else{

			$arr = [
				'tournament_id'=>$this->validate($data->tournament_id),
				'venue_name'=>$this->validate($data->venue_name),
				'country'=>$this->validate($data->country),
				'state'=>$this->validate($data->state),
				'city'=>$this->validate($data->city),
				'no_courts'=>$this->validate($data->no_courts),
				'start_date'=>$this->validate($data->start_date),
				'end_date'=>$this->validate($data->end_date),
				'start_time'=>$this->validate($data->start_time),
				'end_time'=>$this->validate($data->end_time),
				'venue_no'=>'1'
			];
			
			//check tournament id is exist or not venue detail
			$check_stmt = $this->conn->prepare('SELECT * FROM tournament_venues WHERE tournament_details_id=(:tournament_id)');
			$check_stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
			$check_stmt->execute();

			if ($check_stmt->rowCount() > 0){
				return ['error'=>NULL,'data'=>'exist'];
			}else{
				// insert tournament venue detail
				$stmt = $this->conn->prepare("INSERT INTO tournament_venues(venue_no,venue_name,country,state,city,no_courts,start_date,end_date,start_time,end_time,tournament_details_id) VALUES ((:venue_no),(:venue_name),(:country),(:state),(:city),(:no_courts),(:start_date),(:end_date),(:start_time),(:end_time),(:tournament_id))");
				$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
				$stmt->bindParam(":venue_name",$arr['venue_name'] , PDO::PARAM_STR);
				$stmt->bindParam(":country",$arr['country'] , PDO::PARAM_INT);
				$stmt->bindParam(":state",$arr['state'] , PDO::PARAM_INT);
				$stmt->bindParam(":city",$arr['city'] , PDO::PARAM_STR);
				$stmt->bindParam(":no_courts",$arr['no_courts'] , PDO::PARAM_INT);
				$stmt->bindParam(":start_date",$arr['start_date'] , PDO::PARAM_STR);
				$stmt->bindParam(":end_date",$arr['end_date'] , PDO::PARAM_STR);
				$stmt->bindParam(":start_time",$arr['start_time'] , PDO::PARAM_STR);
				$stmt->bindParam(":end_time",$arr['end_time'] , PDO::PARAM_STR);
				$stmt->bindParam(":venue_no",$arr['venue_no'] , PDO::PARAM_INT);
				if($stmt->execute()){
					$id = $this->conn->lastInsertId();
					return ['error'=>NULL,'data'=>['tournament_venue_id'=>$id]];
				}else{
					return ['error'=>NULL,'data'=>''];
				}
			}			

		}
	}
	/*-----------------------STORE TOURNAMENT VENUE DETAIL------------------------------------------ */

	/*-----------------------GET TOURNAMENT VENUE DETAIL-------------------------------------------- */
	public function getTournamentVenueDetail($data){
		$error = new ArrayObject();
		if(!isset($data->tournament_venue_id) || $data->tournament_venue_id == NULL){
			$error->append('Please enter tournament venue id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_venue_id = $this->validate($data->tournament_venue_id);
			$stmt = $this->conn->prepare("SELECT tournament_venues.id,tournament_venues.venue_name,tournament_details.tournament_name,countries.country_name,states.state_name,tournament_venues.city,tournament_venues.no_courts,tournament_venues.start_date,tournament_venues.end_date,tournament_venues.start_time,tournament_venues.end_time FROM tournament_venues 
										LEFT JOIN tournament_details ON  tournament_venues.tournament_details_id = tournament_details.id
										LEFT JOIN countries ON  tournament_venues.country = countries.countries_id
										LEFT JOIN states ON  tournament_venues.state = states.states_id
										WHERE tournament_venues.id = (:tournament_venue_id)");
			$stmt->bindParam(":tournament_venue_id",$tournament_venue_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------GET TOURNAMENT VENUE DETAIL-------------------------------------------- */


	/*-------------------------STORE TOURNAMENT LUNCH TIME DETAIL------------------------ */
	public function storeTournamentLunchTimeDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			//check tournament id is exist or not in tournament lunch time
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_lunch_times WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){
				//insert tournament lunch details with respect to tournament id
					$from_time = '';$to_time = '';
					if(isset($data->from_time) && $data->from_time != NULL){
						$from_time .= $this->validate($data->from_time);
					}

					if(isset($data->to_time) && $data->to_time != NULL){
						$to_time .= $this->validate($data->to_time);
					}
					$arr = [
						'tournament_id'=>$this->validate($data->tournament_id),
						'lunch_time_from'=> $from_time,
						'lunch_time_to'=> $to_time
					];
						
					$stmt = $this->conn->prepare("INSERT INTO tournament_lunch_times(tournament_details_id,lunch_time_from,lunch_time_to) VALUES ((:tournament_id),(:from_time),(:to_time))");
					$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":from_time",$arr['lunch_time_from'] , PDO::PARAM_STR);
					$stmt->bindParam(":to_time",$arr['lunch_time_to'] , PDO::PARAM_STR);
					if($stmt->execute()){
						$id = $this->conn->lastInsertId();
						return ['error'=>NULL,'data'=>['tournament_lunchtime_id'=>$id]];
					}else{
						return ['error'=>NULL,'data'=>''];
					}

			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
			

		}
	
	}
	/*-------------------------STORE TOURNAMENT LUNCH TIME DETAIL--------------------------------- */


	/*-----------------------GET TOURNAMENT LUNCH TIME DETAIL-----------------------------------*/
	public function getTournamentLunchTimeDetail($data){
		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT * FROM tournament_lunch_times	WHERE tournament_details_id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------GET TOURNAMENT LUNCH TIME DETAIL-------------------------------------------- */


	/*-----------------------STORE TOURNAMENT GAME RULE DETAIL-------------------------------------------*/
	public function storeTournamentGameRuleDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->shuttles_used) || $data->shuttles_used == NULL){
			$error->append('Please enter shuttle used detail.');
		}

		if(!isset($data->company_name) || $data->company_name == NULL){
			$error->append('Please enter company name.');
		}

		if(!isset($data->is_umpire_decision_final) || $data->is_umpire_decision_final == NULL){
			$error->append('Please enter umpire final decision.');
		}

		if(!isset($data->participation_certificate_for_all) || $data->participation_certificate_for_all == NULL){
			$error->append('Please enter participation certificate.');
		}

		if(!isset($data->free_food) || $data->free_food == NULL){
			$error->append('Please enter free food.');
		}

		if(!isset($data->topest_not_allowed_players) || $data->topest_not_allowed_players == NULL){
			$error->append('Please enter not allowed topest player.');
		}

		if(!isset($data->prior_reporting_min) || $data->prior_reporting_min == NULL){
			$error->append('Please enter prior reporting min.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			//check tournament id is exist or not in tournament game rules
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_game_rules WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){
				//insert tournament games rules details with respect to tournament id
					
					$arr = [
						'tournament_id'=>$this->validate($data->tournament_id),
						'shuttles_used'=>$this->validate($data->shuttles_used),
						'company_name'=>$this->validate($data->company_name),
						'is_umpire_decision_final'=>$this->validate($data->is_umpire_decision_final),
						'participation_certificate_for_all'=>$this->validate($data->participation_certificate_for_all),
						'free_food'=>$this->validate($data->free_food),
						'topest_not_allowed_players'=>$this->validate($data->topest_not_allowed_players),
						'prior_reporting_min'=>$this->validate($data->prior_reporting_min)
					];
						
					$stmt = $this->conn->prepare("INSERT INTO tournament_game_rules(tournament_details_id,shuttles_used,company_name,is_umpire_decision_final,participation_certificate_for_all,free_food,topest_not_allowed_players,prior_reporting_min) VALUES ((:tournament_id),(:shuttles_used),(:company_name),(:is_umpire_decision_final),(:participation_certificate_for_all),(:free_food),(:topest_not_allowed_players),(:prior_reporting_min))");
					$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":shuttles_used",$arr['shuttles_used'] , PDO::PARAM_STR);
					$stmt->bindParam(":company_name",$arr['company_name'] , PDO::PARAM_STR);
					$stmt->bindParam(":is_umpire_decision_final",$arr['is_umpire_decision_final'] , PDO::PARAM_STR);
					$stmt->bindParam(":participation_certificate_for_all",$arr['participation_certificate_for_all'] , PDO::PARAM_STR);
					$stmt->bindParam(":free_food",$arr['free_food'] , PDO::PARAM_STR);
					$stmt->bindParam(":topest_not_allowed_players",$arr['topest_not_allowed_players'] , PDO::PARAM_INT);
					$stmt->bindParam(":prior_reporting_min",$arr['prior_reporting_min'] , PDO::PARAM_INT);

					if($stmt->execute()){
						$id = $this->conn->lastInsertId();
						return ['error'=>NULL,'data'=>['tournament_game_rule_id'=>$id]];
					}else{
						return ['error'=>NULL,'data'=>''];
					}

			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
			

		}
	
	}
	/*-----------------------STORE TOURNAMENT GAME RULE DETAIL-------------------------------------------*/


	/*-----------------------GET TOURNAMENT GAME RULE DETAIL-------------------------------------------- */
	public function getTournamentGameRuleDetail($data){
		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_game_rules.*,tournament_details.tournament_name FROM tournament_game_rules LEFT JOIN tournament_details ON tournament_game_rules.tournament_details_id=tournament_details.id WHERE tournament_game_rules.tournament_details_id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------GET TOURNAMENT GAME RULE DETAIL-------------------------------------------- */


	/*-------------------------STORE TOURNAMENT PRIZE DETAIL--------------------------------------------- */
	public function storeTournamentPrizeDetail($data,$file_data){
	
		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->winner_trophy) || $data->winner_trophy == NULL){
			$error->append('Please enter winner trophy.');
		}

		if(!isset($data->runner_trophy) || $data->runner_trophy == NULL){
			$error->append('Please enter ruuner trophy.');
		}

		if(!isset($data->semifinalist_trophy) || $data->semifinalist_trophy == NULL){
			$error->append('Please enter semifinalist trophy.');
		}

		if(!isset($data->winner_medal) || $data->winner_medal == NULL){
			$error->append('Please enter winner medal.');
		}

		if(!isset($data->runner_medal) || $data->runner_medal == NULL){
			$error->append('Please enter runner medal.');
		}

		if(!isset($data->semifinalist_medal) || $data->semifinalist_medal == NULL){
			$error->append('Please enter semifinalist medal.');
		}

		if(!isset($data->winner_goodies) || $data->winner_goodies == NULL){
			$error->append('Please enter winner goodies.');
		}

		if(!isset($data->w_goodie_name) || $data->w_goodie_name == NULL){
			$error->append('Please enter winner goodie name.');
		}

		if(!isset($data->runner_goodies) || $data->runner_goodies == NULL){
			$error->append('Please enter runner goodies.');
		}

		if(!isset($data->r_goodie_name) || $data->r_goodie_name == NULL){
			$error->append('Please enter runner goodie name.');
		}

		if(!isset($data->semifinalist_goodies) || $data->semifinalist_goodies == NULL){
			$error->append('Please enter semifinalist goodies.');
		}

		if(!isset($data->s_goodie_name) || $data->s_goodie_name == NULL){
			$error->append('Please enter ssemifinalist goodie name.');
		}

		if(!isset($data->winner_cash_prize) || $data->winner_cash_prize == NULL){
			$error->append('Please enter winner cash prize.');
		}

		if(!isset($data->w_cash_amount) || $data->w_cash_amount == NULL){
			$error->append('Please enter winner cash amount.');
		}

		if(!isset($data->runner_cash_prize) || $data->runner_cash_prize == NULL){
			$error->append('Please enter runner cash prize.');
		}

		if(!isset($data->r_cash_amount) || $data->r_cash_amount == NULL){
			$error->append('Please enter runner cash amount.');
		}

		if(!isset($data->semifinalist_cash_prize) || $data->semifinalist_cash_prize == NULL){
			$error->append('Please enter semifinalist cash prize.');
		}

		if(!isset($data->s_cash_amount) || $data->s_cash_amount == NULL){
			$error->append('Please enter semifinallist cash amount.');
		}

		if(!isset($data->other_details) || $data->other_details == NULL){
			$error->append('Please enter other details.');
		}
		
		if(isset($file_data) || $file_data != NULL){
			$allowedExts = array("jpg", "jpeg", "png");
			$extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
			if(!in_array($extension, $allowedExts)){
				$error->append('Please enter only jpg, jpeg, png file format.');				
			}
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			//check tournament id is exist or not in tournament prize
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_prizes WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){				
				
				//insert tournament prize with respect to tournament id					
					$arr = [
						'tournament_id'=>$this->validate($data->tournament_id),
						'winner_trophy'=>$this->validate($data->winner_trophy),
						'runner_trophy'=>$this->validate($data->runner_trophy),
						'semifinalist_trophy'=>$this->validate($data->semifinalist_trophy),
						'winner_medal'=>$this->validate($data->winner_medal),
						'runner_medal'=>$this->validate($data->runner_medal),
						'semifinalist_medal'=>$this->validate($data->semifinalist_medal),
						'winner_goodies'=>$this->validate($data->winner_goodies),
						'w_goodie_name'=>$this->validate($data->w_goodie_name),
						'runner_goodies'=>$this->validate($data->runner_goodies),
						'r_goodie_name'=>$this->validate($data->r_goodie_name),
						'semifinalist_goodies'=>$this->validate($data->semifinalist_goodies),
						's_goodie_name'=>$this->validate($data->s_goodie_name),
						'winner_cash_prize'=>$this->validate($data->winner_cash_prize),
						'w_cash_amount'=>$this->validate($data->w_cash_amount),
						'runner_cash_prize'=>$this->validate($data->runner_cash_prize),
						'r_cash_amount'=>$this->validate($data->r_cash_amount),
						'semifinalist_cash_prize'=>$this->validate($data->semifinalist_cash_prize),
						's_cash_amount'=>$this->validate($data->s_cash_amount),
						'other_details'=>$this->validate($data->other_details)
					];
						
					$stmt = $this->conn->prepare("INSERT INTO tournament_prizes(tournament_details_id,winner_trophy,runner_trophy,semifinalist_trophy,winner_medal,runner_medal,semifinalist_medal,winner_goodies,w_goodie_name,runner_goodies,r_goodie_name,semifinalist_goodies,s_goodie_name,winner_cash_prize,w_cash_amount,runner_cash_prize,r_cash_amount,semifinalist_cash_prize,s_cash_amount) VALUES ((:tournament_id),(:winner_trophy),(:runner_trophy),(:semifinalist_trophy),(:winner_medal),(:runner_medal),(:semifinalist_medal),(:winner_goodies),(:w_goodie_name),(:runner_goodies),(:r_goodie_name),(:semifinalist_goodies),(:s_goodie_name),(:winner_cash_prize),(:w_cash_amount),(:runner_cash_prize),(:r_cash_amount),(:semifinalist_cash_prize),(:s_cash_amount))");
					$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":winner_trophy",$arr['winner_trophy'] , PDO::PARAM_STR);
					$stmt->bindParam(":runner_trophy",$arr['runner_trophy'] , PDO::PARAM_STR);
					$stmt->bindParam(":semifinalist_trophy",$arr['semifinalist_trophy'] , PDO::PARAM_STR);
					$stmt->bindParam(":winner_medal",$arr['winner_medal'] , PDO::PARAM_STR);
					$stmt->bindParam(":runner_medal",$arr['runner_medal'] , PDO::PARAM_STR);
					$stmt->bindParam(":semifinalist_medal",$arr['semifinalist_medal'] , PDO::PARAM_STR);
					$stmt->bindParam(":winner_goodies",$arr['winner_goodies'] , PDO::PARAM_STR);
					$stmt->bindParam(":w_goodie_name",$arr['w_goodie_name'] , PDO::PARAM_STR);
					$stmt->bindParam(":runner_goodies",$arr['runner_goodies'] , PDO::PARAM_STR);
					$stmt->bindParam(":r_goodie_name",$arr['r_goodie_name'] , PDO::PARAM_STR);
					$stmt->bindParam(":semifinalist_goodies",$arr['semifinalist_goodies'] , PDO::PARAM_STR);
					$stmt->bindParam(":s_goodie_name",$arr['s_goodie_name'] , PDO::PARAM_STR);
					$stmt->bindParam(":winner_cash_prize",$arr['winner_cash_prize'] , PDO::PARAM_STR);
					$stmt->bindParam(":w_cash_amount",$arr['w_cash_amount'] , PDO::PARAM_INT);
					$stmt->bindParam(":runner_cash_prize",$arr['runner_cash_prize'] , PDO::PARAM_STR);
					$stmt->bindParam(":r_cash_amount",$arr['r_cash_amount'] , PDO::PARAM_INT);
					$stmt->bindParam(":semifinalist_cash_prize",$arr['semifinalist_cash_prize'] , PDO::PARAM_STR);
					$stmt->bindParam(":s_cash_amount",$arr['s_cash_amount'] , PDO::PARAM_INT);

					if($stmt->execute()){
						$id = $this->conn->lastInsertId();

						//upload image and update into tournament detail
						$newfilename = '';
						if(isset($file_data) || $file_data != NULL){
							$accountId = $this->user_id;
							$temp = explode(".",$file_data['name']);
							$newfilename .= 'U'.$accountId.'Tournament'.rand().'.'.end($temp);
							move_uploaded_file($file_data["tmp_name"],$_SERVER['DOCUMENT_ROOT']."/msa/playtournament/images/" . $newfilename);
						}
						$td_stmt = $this->conn->prepare('UPDATE tournament_details SET other_details=(:other_details),image=(:image) where id=(:tournament_id)');
						$td_stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
						$td_stmt->bindParam(":other_details",$arr['other_details'] , PDO::PARAM_STR);
						$td_stmt->bindParam(":image",$newfilename , PDO::PARAM_STR);
						$td_stmt->execute();

						return ['error'=>NULL,'data'=>['tournament_prize_id'=>$id]];
					}else{
						return ['error'=>NULL,'data'=>''];
					}

			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
		}
	
	}
	/*-------------------------STORE TOURNAMENT PRIZE DETAIL------------------------------------------------- */


	/*-----------------------GET TOURNAMENT PRIZE DETAIL-------------------------------------------- */
	public function getTournamentPrizeDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_prizes.*,tournament_details.tournament_name,tournament_details.other_details,tournament_details.image FROM tournament_prizes LEFT JOIN tournament_details ON tournament_prizes.tournament_details_id=tournament_details.id WHERE tournament_prizes.tournament_details_id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------GET TOURNAMENT PRIZE DETAIL-------------------------------------------- */


	/*-----------------------GET CATEGORY DETAIL--------------------------------------------------- */
	public function getCategoryDetail($data){
		
		$tournament_id = isset($data->tournament_id) ? $data->tournament_id : '';
		$stmt = $this->conn->prepare("SELECT id,category_name,cat_code FROM tournament_category_master_table");
		$stmt->execute();
		$arr = [];
		if ($stmt->rowCount() > 0){

			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

				$cat_id = $row['id'];
				$subcat_arr = [];
				if($tournament_id != ""){
					$sc_stmt = $this->conn->prepare("SELECT * FROM tournament_subcategory_master_table where category_id=(:cat_id) and id NOT IN (SELECT subcategory_id FROM tournament_categories WHERE tournament_details_id=(:id))");
					$sc_stmt->bindParam(':cat_id',$cat_id,PDO::PARAM_INT);
					$sc_stmt->bindParam(':id',$tournament_id,PDO::PARAM_INT);
				}else{
					if($cat_id != ""){
						$sc_stmt = $this->conn->prepare("SELECT * FROM tournament_subcategory_master_table where category_id=(:cat_id)");
						$sc_stmt->bindParam(':cat_id',$cat_id,PDO::PARAM_INT);
					}else{
						$sc_stmt = $this->conn->prepare("SELECT * FROM tournament_subcategory_master_table");
					}
				}
				$sc_stmt->execute();							
				if ($sc_stmt->rowCount() > 0){

					while($sc_row = $sc_stmt->fetch(PDO::FETCH_ASSOC)){
						array_push($subcat_arr,$sc_row);
					}
				}
				array_push($arr,['id'=>$row['id'],'category_name'=>$row['category_name'],'subcategories'=>$subcat_arr]);
			}
			if(count($arr) == 0){
				return ['error'=>NULL,'data'=>$arr];
			}else{
				return ['error'=>NULL,'data'=>$arr];
			}
		
		}
	}
	/*-----------------------GET CATEGORY DETAIL--------------------------------------------------- */


	/*----------------------------STORE TOURNAMENT CATEGORY DETAIL---------------------------------- */
	public function storeTournamentCategoryDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->category_id) || $data->category_id == NULL){
			$error->append('Please enter category id.');
		}

		if(!isset($data->subcategory_id) || $data->subcategory_id == NULL){
			$error->append('Please enter subcategory id.');
		}

		if(!isset($data->min_no_entries) || $data->min_no_entries == NULL){
			$error->append('Please enter min no. of entries.');
		}

		if(!isset($data->fee) || $data->fee == NULL){
			$error->append('Please enter fee.');
		}

		if(!isset($data->venue_id) || $data->venue_id == NULL){
			$error->append('Please enter venue id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			// check tournament, subcategory, venue exist into tournament category or not.
			$check_stmt = $this->conn->prepare("SELECT * from tournament_categories WHERE tournament_details_id=(:tournament_details_id) AND subcategory_id = (:subcategory_id) AND tournament_venues_id = (:tournament_venues_id)");
			$check_stmt->bindParam(":tournament_details_id", $data->tournament_id, PDO::PARAM_INT);
			$check_stmt->bindParam(":subcategory_id", $data->subcategory_id, PDO::PARAM_INT);
			$check_stmt->bindParam(":tournament_venues_id", $data->venue_id, PDO::PARAM_INT);
			$check_stmt->execute();
			if($check_stmt->rowCount() > 0){
				return ['error'=>NULL,'data'=>'exist'];
			}else{

				//get tournament venues
				$venue_list = '';
				$tv_stmt = $this->conn->prepare("SELECT id,venue_no,venue_name FROM tournament_venues where tournament_details_id=(:tournament_details_id)");
				$tv_stmt->bindParam(":tournament_details_id", $data->tournament_id, PDO::PARAM_INT);
				$tv_stmt->execute();
				if ($tv_stmt->rowCount() > 0){
					while ($row = $tv_stmt->fetch(PDO::FETCH_ASSOC)){
						$venue_list .='<option value="'.$row['id'].'"  selected><strong>'.$row['venue_name'].'</strong></option>';
					}
				}

				// insert into tournament category
				$arr = [
					'reporting_time'=>'',
					'min_no_entries'=>$this->validate($data->min_no_entries),
					'fee'=>$this->validate($data->fee),
					'tournament_venues_id'=>$this->validate($data->venue_id),
					'subcategory_id'=>$this->validate($data->subcategory_id),
					'tournament_details_id'=>$this->validate($data->tournament_id)
				];

				$stmt = $this->conn->prepare("INSERT INTO tournament_categories(reporting_time,min_no_entries,fee,tournament_venues_id,subcategory_id,tournament_details_id) value (:reporting_time,:min_no_entries,:fee,:tournament_venues_id,:subcategory_id,:tournament_details_id)");
				$stmt->bindParam(":reporting_time", $arr['reporting_time'], PDO::PARAM_STR);
				$stmt->bindParam(":min_no_entries", $arr['min_no_entries'], PDO::PARAM_INT);
				$stmt->bindParam(":fee", $arr['fee'], PDO::PARAM_INT);
				$stmt->bindParam(":tournament_venues_id", $arr['tournament_venues_id'], PDO::PARAM_INT);
				$stmt->bindParam(":subcategory_id", $arr['subcategory_id'], PDO::PARAM_INT);
				$stmt->bindParam(":tournament_details_id",$arr['tournament_details_id'], PDO::PARAM_INT);
				if($stmt->execute()){
					$id = $this->conn->lastInsertId();

					// get category
					$cat_stmt = $this->conn->prepare("SELECT  tc.*,subcat.subcategory_name,subcat.category_id,tv.venue_no,tv.venue_name FROM tournament_categories tc LEFT JOIN tournament_subcategory_master_table subcat on tc.subcategory_id=subcat.id LEFT JOIN tournament_venues tv on tc.tournament_venues_id=tv.id WHERE tc.tournament_details_id=(:tournament_details_id) and subcat.category_id=(:category_id) ");
					$cat_stmt->bindParam(":tournament_details_id", $data->tournament_id, PDO::PARAM_INT);
					$cat_stmt->bindParam(":category_id", $data->category_id, PDO::PARAM_INT);
					$cat_stmt->execute();
					$cat_list = '';
					if ($cat_stmt->rowCount() > 0){
						while($row2 = $cat_stmt->fetch(PDO::FETCH_ASSOC)){
							$cat_list .= '<div class="form-row"><div class="form-group col-sm-2"><span class="form-check form-check-inline"><select class="browser-default custom-select" name="category" id="category" readonly><option value="'.$row2['subcategory_id'].'" selected><strong>'.$row2['subcategory_name'].'</strong></option></select></span></div><div class="form-group col-sm-2"><input type="text" class="form-control" name="min_no_entries" id="min_no_entries" placeholder="Min no. of entries" readonly value="'.$row2['min_no_entries'].'"></div><div class="form-group col-sm-2"><input type="text" class="form-control" name="fee" id="fee" placeholder="Fee" value="'.$row2['fee'].'" readonly></div><div class="form-group col-sm-2"><select id="venue" class="browser-default custom-select" name="venue" readonly><option value="'.$row2['tournament_venues_id'].'" selected><strong>'.$row2['venue_name'].'</strong></option></select></div><div class="form-group col-sm-1"><button type="button" class="btn btn-sm bg-default text-white del p-2" id1="'.$data->category_id.'" id="'.$row2['id'].'"><i class="fa fa-trash" aria-hidden="true"></i></button></div></div>';								
						}						
					}

					// get subcategory
					$subcat_stmt = $this->conn->prepare("SELECT * FROM tournament_subcategory_master_table where category_id=(:category_id) ");				
					$subcat_stmt->bindParam(":category_id", $data->category_id, PDO::PARAM_INT);
					$subcat_stmt->execute();
					$subcat_list = '';
					if ($subcat_stmt->rowCount() > 0){
						while($row3 = $subcat_stmt->fetch(PDO::FETCH_ASSOC)){
							$subcat_list .= '<option value="'.$row3['id'].'">'.$row3['subcategory_name'].'</option>';								
						}						
					}

					$cat_list .= '<div class="form-row"><div class="form-group col-sm-2"><span class="form-check form-check-inline"><select class="browser-default custom-select" name="'.$data->category_id.'category" id="'.$data->category_id.'category" required ><option value="" selected><strong>Select Category</strong></option>'.$subcat_list.'</select></span></div><div class="form-group col-sm-2"><input type="text" class="form-control" name="'.$data->category_id.'min_no_entries" id="'.$data->category_id.'min_no_entries" placeholder="Min no. of entries" required ></div><div class="form-group col-sm-2"><input type="text" class="form-control" name="'.$data->category_id.'fee" id="'.$data->category_id.'fee" placeholder="Fee" required></div><div class="form-group col-sm-2"><select id="'.$data->category_id.'venue" class="browser-default custom-select" name="'.$data->category_id.'venue" required >'.$venue_list.'</select></div><div class="form-group col-sm-1"><button type="button" class="btn btn-sm bg-default text-white add p-2" id1="'.$data->category_id.'"><i class="fa fa-plus" aria-hidden="true"></i></button><button type="button" class="btn btn-sm bg-default text-white p-2" id="del"><i class="fa fa-trash" aria-hidden="true"></i></button></div></div>';
					return ['error'=>NULL,'data'=>['tournament_category_id'=>$id,'list'=>$cat_list]];
				}else{
					return ['error'=>NULL,'data'=>[]];	
				}

			}
			
		}

	}
	/*----------------------------STORE TOURNAMENT CATEGORY DETAIL---------------------------------- */


	/*-----------------------------GET TOURNAMENT CATEGORY DETAIL------------------------------------ */
	public function getTournamentCategoryDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_categories.*,tournament_details.tournament_name,tournament_subcategory_master_table.subcategory_name,tournament_venues.venue_name FROM tournament_categories LEFT JOIN tournament_details ON tournament_categories.tournament_details_id=tournament_details.id LEFT JOIN tournament_subcategory_master_table ON tournament_subcategory_master_table.id = tournament_categories.subcategory_id LEFT JOIN tournament_venues ON tournament_venues.id = tournament_categories.tournament_venues_id WHERE tournament_categories.tournament_details_id = (:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			$arr = [];
			if ($stmt->rowCount() > 0){
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					array_push($arr,$row);
				}

				return ['error'=>NULL,'data'=>$arr];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------------GET TOURNAMENT CATEGORY DETAIL------------------------------------ */


	/*-----------------------------GET NO OF COURTS DETAIL-------------------------------------------- */
	public function getNoOfCourtDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{	
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT sum(no_courts) as sum FROM tournament_venues WHERE tournament_details_id=(:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			$arr = [];
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];
				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*-----------------------------GET NO OF COURTS DETAIL-------------------------------------------- */


	/*------------------------------STORE TOURNAMENT UMPIRE DETAIL------------------------------------ */
	public function storeTournamentUmpireDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->no_of_umpire) || $data->no_of_umpire == NULL){
			$error->append('Please enter no of umpire.');
		}

		if(!isset($data->firstname) || $data->firstname == NULL){
			$error->append('Please enter first name.');
		}

		if(!isset($data->username) || $data->username == NULL){
			$error->append('Please enter user name.');
		}

		if(!isset($data->password) || $data->password == NULL){
			$error->append('Please enter password.');
		}		

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			
			//check tournament id is exist or not in tournament umpire
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_umpire_details WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){
				//insert tournament umpire details with respect to tournament id

					$t = 0; $r = [];
					while($t < $data->no_of_umpire){
						$arr = [
							'tournament_id'=>$this->validate($data->tournament_id),
							'firstname'=>$this->validate($data->firstname[$t]),
							'username'=>$this->validate($data->username[$t]),
							'password'=>$this->validate($data->password[$t])
						];						
						$stmt = $this->conn->prepare("INSERT INTO tournament_umpire_details(tournament_details_id,firstname,username,password) VALUES ((:tournament_id),(:firstname),(:username),(:password))");
						$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
						$stmt->bindParam(":firstname",$arr['firstname'] , PDO::PARAM_STR);
						$stmt->bindParam(":username",$arr['username'] , PDO::PARAM_STR);
						$stmt->bindParam(":password",$arr['password'] , PDO::PARAM_STR);
						$stmt->execute();
						$id = $this->conn->lastInsertId();
						array_push($r,$id);	
						$t++;
					}
					
						
					return ['error'=>NULL,'data'=>['tournament_umpire_id'=>$r]];

			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
			

		}
	}
	/*------------------------------STORE TOURNAMENT UMPIRE DETAIL------------------------------------ */


	/*------------------------------GET TOURNAMENT UMPIRE DETAIL--------------------------------------- */
	public function getTournamentUmpireDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{	
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_umpire_details.*,tournament_details.tournament_name FROM tournament_umpire_details LEFT JOIN tournament_details ON tournament_details.id = tournament_umpire_details.tournament_details_id WHERE tournament_umpire_details.tournament_details_id=(:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			$arr = [];
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*------------------------------GET TOURNAMENT UMPIRE DETAIL--------------------------------------- */


	/*------------------------------DELETE TOURNAMENT UMPIRE DETAIL------------------------------------- */
	public function deleteTournamentUmpireDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("DELETE FROM tournament_umpire_details WHERE tournament_details_id=(:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			return ['error'=>NULL,'data'=>'success'];
		}
	}
	/*------------------------------DELETE TOURNAMENT UMPIRE DETAIL------------------------------------- */


	/*------------------------------STORE TOURANMENT HOSPITAL HOTEL DETAIL------------------------------------ */
	public function storeTournamentHospitalHotelDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->hotel1) || $data->hotel1 == NULL){
			$error->append('Please enter hotel1.');
		}

		if(!isset($data->hotel2) || $data->hotel2 == NULL){
			$error->append('Please enter hotel2.');
		}

		if(!isset($data->hotel3) || $data->hotel3 == NULL){
			$error->append('Please enter hotel3.');
		}

		if(!isset($data->hospital1) || $data->hospital1 == NULL){
			$error->append('Please enter hospital1.');
		}		

		if(!isset($data->hospital2) || $data->hospital2 == NULL){
			$error->append('Please enter hospital2.');
		}

		if(!isset($data->hospital3) || $data->hospital3 == NULL){
			$error->append('Please enter hospital3.');
		}
		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{

			//check tournament id is exist or not in tournament hospital hotel
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_hospitals_hotels WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){

				$arr = [
					'tournament_id'=>$this->validate($data->tournament_id),
					'hotel1'=>$this->validate($data->hotel1),
					'hotel2'=>$this->validate($data->hotel2),
					'hotel3'=>$this->validate($data->hotel3),
					'hospital1'=>$this->validate($data->hospital1),
					'hospital2'=>$this->validate($data->hospital2),
					'hospital3'=>$this->validate($data->hospital3)
				];	

				$stmt = $this->conn->prepare("INSERT INTO tournament_hospitals_hotels(tournament_details_id,hotel1,hotel2,hotel3,hospital1,hospital2,hospital3) VALUES ((:tournament_id),(:hotel1),(:hotel2),(:hotel3),(:hospital1),(:hospital2),(:hospital3))");
				$stmt->bindParam(":tournament_id",$arr['tournament_id'] , PDO::PARAM_INT);
				$stmt->bindParam(":hotel1",$arr['hotel1'] , PDO::PARAM_STR);
				$stmt->bindParam(":hotel2",$arr['hotel2'] , PDO::PARAM_STR);
				$stmt->bindParam(":hotel3",$arr['hotel3'] , PDO::PARAM_STR);
				$stmt->bindParam(":hospital1",$arr['hospital1'] , PDO::PARAM_STR);
				$stmt->bindParam(":hospital2",$arr['hospital2'] , PDO::PARAM_STR);
				$stmt->bindParam(":hospital3",$arr['hospital3'] , PDO::PARAM_STR);
				$stmt->execute();
				$id = $this->conn->lastInsertId();
				return ['error'=>NULL,'data'=>['tournament_hospital_id'=>$id]];
			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
		}
	}
	/*------------------------------STORE TOURANMENT HOSPITAL HOTEL DETAIL------------------------------------ */


	/*------------------------------GET TOURNAMENT HOSPITAL HOTEL DETAIL-------------------------------------- */
	public function getTournamentHospitalHotelDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{	
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_hospitals_hotels.*,tournament_details.tournament_name FROM tournament_hospitals_hotels LEFT JOIN tournament_details ON tournament_details.id = tournament_hospitals_hotels.tournament_details_id WHERE tournament_hospitals_hotels.tournament_details_id=(:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return ['error'=>NULL,'data'=>$row];				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*------------------------------GET TOURNAMENT HOSPITAL HOTEL DETAIL-------------------------------------- */


	/*------------------------------STORE TOURNAMENT PAYMENT MODE DETAIL------------------------------------------ */
	public function storeTournamentPaymentModeDetail($data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->payment_mode) || $data->payment_mode == NULL){
			$error->append('Please enter tournament payment mode.');
		}


		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$stmt = $this->conn->prepare("UPDATE tournament_details SET t_organizer_paymentmode=(:t_orgmode) WHERE id=(:tournament_id)");
			$stmt->bindParam(":t_orgmode",$data->payment_mode,PDO::PARAM_INT);
			$stmt->bindParam(":tournament_id",$data->tournament_id,PDO::PARAM_INT);
			$stmt->execute();
			return ['error'=>NULL,'data'=>'success'];
		}
	}
	/*------------------------------STORE TOURNAMENT PAYMENT MODE DETAIL------------------------------------------ */


	/*------------------------------STORE TOURNAMENT UPI DETAIL------------------------------------------ */
	public function storeTournamentUpiDetail($data,$file_data){

		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}

		if(!isset($data->payment_mode) || $data->payment_mode == NULL){
			$error->append('Please enter payment mode.');
		}

		if(!isset($data->gpay_num) || $data->gpay_num == NULL){
			$error->append('Please enter google pay no.');
		}

		if(!isset($data->phonepay_num) || $data->phonepay_num == NULL){
			$error->append('Please enter phone pay no.');
		}

		if(!isset($data->paytm_num) || $data->paytm_num == NULL){
			$error->append('Please enter paytm no.');
		}

		if(!isset($data->mobikwik) || $data->mobikwik == NULL){
			$error->append('Please enter mobikwik no.');
		}

		if(!isset($data->amazonpay) || $data->amazonpay == NULL){
			$error->append('Please enter amazonpay no.');
		}

		if(!isset($data->upiid_num) || $data->upiid_num == NULL){
			$error->append('Please enter upi no.');
		}

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			//check tournament id is exist or not in tournament upi 
			$check_stmt = $this->conn->prepare("SELECT * FROM tournament_upi_details WHERE tournament_details_id=(:tournament_id)");
			$check_stmt->bindParam(":tournament_id",$data->tournament_id , PDO::PARAM_INT);
			$check_stmt->execute();     

			if ($check_stmt->rowCount() == 0){

				if($data->payment_mode == 1 || $data->payment_mode == 3){

					$arr = [
						'gpay_number'=>$this->validate($data->gpay_num),
						'phonepay_number'=>$this->validate($data->phonepay_num),
						'paytm_number'=>$this->validate($data->paytm_num),
						'mobikwik_number'=>$this->validate($data->mobikwik),
						'amazonpay_number'=>$this->validate($data->amazonpay),
						'upi_id'=>$this->validate($data->upiid_num),
						'tournament_details_id'=>$this->validate($data->tournament_id)
					];
					//upload image and update into tournament detail
					$newfilename = '';
					if(isset($file_data) || $file_data != NULL){
						$accountId = $this->user_id;
						$temp = explode(".",$file_data['name']);
						$newfilename .= 'qr'.$accountId.'Tournament'.rand().'.'.end($temp);
						move_uploaded_file($file_data["tmp_name"],$_SERVER['DOCUMENT_ROOT']."/msa/playtournament/images/" . $newfilename);
						$arr['qrcode'] = $newfilename;
					}
					$stmt = $this->conn->prepare("INSERT INTO tournament_upi_details(tournament_details_id,gpay_number,phonepay_number,paytm_number,mobikwik_number,amazonpay_number,upi_id,qrcode) VALUES ((:tournament_id),(:gpay_number),(:phonepay_number),(:paytm_number),(:mobikwik_number),(:amazonpay_number),(:upi_id),(:qrcode))");
					$stmt->bindParam(":tournament_id",$arr['tournament_details_id'] , PDO::PARAM_INT);
					$stmt->bindParam(":gpay_number",$arr['gpay_number'] , PDO::PARAM_STR);
					$stmt->bindParam(":phonepay_number",$arr['phonepay_number'] , PDO::PARAM_STR);
					$stmt->bindParam(":paytm_number",$arr['paytm_number'] , PDO::PARAM_STR);
					$stmt->bindParam(":mobikwik_number",$arr['mobikwik_number'] , PDO::PARAM_STR);
					$stmt->bindParam(":amazonpay_number",$arr['amazonpay_number'] , PDO::PARAM_STR);
					$stmt->bindParam(":upi_id",$arr['upi_id'] , PDO::PARAM_STR);
					$stmt->bindParam(":qrcode",$arr['qrcode'] , PDO::PARAM_STR);
					$stmt->execute();
					$id = $this->conn->lastInsertId();
					return ['error'=>NULL,'data'=>['tournament_upi_id'=>$id]];

				}				

			}else{
				return ['error'=>NULL,'data'=>'exist'];
			}
		}
	}
	/*------------------------------STORE TOURNAMENT UPI DETAIL------------------------------------------ */
	
	/*------------------------------GET TOURNAMENT UPI DETAIL--------------------------------------------*/
	public function getTournamentUpiDetail($data){
		
		$error = new ArrayObject();
		if(!isset($data->tournament_id) || $data->tournament_id == NULL){
			$error->append('Please enter tournament id.');
		}		

		if(sizeof($error) > 0){
			return ['error'=>$error];
		}else{
			$tournament_id = $this->validate($data->tournament_id);
			$stmt = $this->conn->prepare("SELECT tournament_upi_details.*,tournament_details.tournament_name FROM tournament_upi_details LEFT JOIN tournament_details ON tournament_details.id = tournament_upi_details.tournament_details_id WHERE tournament_upi_details.tournament_details_id=(:tournament_id)");
			$stmt->bindParam(":tournament_id",$tournament_id , PDO::PARAM_INT);
			$stmt->execute();
			if ($stmt->rowCount() > 0){
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				$row['qrcode'] = $_SERVER['DOCUMENT_ROOT']."/msa/playtournament/images/" . $row['qrcode'];
				return ['error'=>NULL,'data'=>$row];				
			}else{
				return ['error'=>NULL,'data'=>''];
			}
		}
	}
	/*------------------------------GET TOURNAMENT UPI DETAIL--------------------------------------------*/



}
?>