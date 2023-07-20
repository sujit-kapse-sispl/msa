<?php
class Tournaments
{
	private $conn;
	private $table = "tournament_details";
	
	public $org_id;
	public $tournament_id ;
	public $category_id ;
	public $court_id;
	public $match_start_time;
	public $match_end_time;
	public $match_date;

	
	
	public function __construct($db)
	{
		$this->conn=$db;
	}
	
	public function getLatestTournament()
	{
		$query = "SELECT td.id,tv.no_courts, td.tournament_name,td.`t_start_date`,td.`t_end_date`
					FROM `tournament_details` td
					LEFT JOIN tournament_venues  tv on td.id=tv.tournament_details_id 
					WHERE `users_id` = (:id) order by td.id desc limit 1;";
					
		$stmt=$this->conn->prepare($query);
		$stmt->bindParam(":id", $this->org_id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt;
	}
	
	public function getTournamentMatches($tournament_id){
		
		$query = "SELECT `category_id` FROM `msa_fixture_dtl` WHERE `tournament_id` = (:tournament_id) GROUP BY `category_id` ;";
					
		$stmt=$this->conn->prepare($query);
		$stmt->bindParam(":tournament_id", $tournament_id, PDO::PARAM_INT);		
		$stmt->execute();
		
		$arr = [];
		while($result = $stmt->fetch(PDO::FETCH_ASSOC)){

			$match_query = "SELECT `court_id`,`match_start_time`,`match_end_time`,`match_date` FROM `msa_fixture_dtl` 
					WHERE `tournament_id` = (:tournament_id) AND `category_id` = (:category_id) ;";
					
			$match_stmt = $this->conn->prepare($match_query);
			$match_stmt->bindParam(":tournament_id", $tournament_id, PDO::PARAM_INT);
			$match_stmt->bindParam(":category_id",$result['category_id'], PDO::PARAM_INT);		
			$match_stmt->execute();

			$data = [];
			while($match_result = $match_stmt->fetch(PDO::FETCH_ASSOC)){
				array_push($data,$match_result);
			}
			array_push($arr,['category_id'=>$result['category_id'],'match'=>$data]);			
			
		}
		return $arr;

	}
	
	public function getCourtDetailsByID()
	{
		$query = "SELECT * FROM `msa_fixture_dtl`  					
					WHERE `tournament_id` = (:tournament_id)  
					and `category_id` = (:category_id) 
					and `court_id` = (:court_id) 
					and `match_start_time` = (:match_start_time) 
					and `match_end_time` = (:match_end_time)
					and `match_date` = (:match_date) ;";
					
		$stmt=$this->conn->prepare($query);
		$stmt->bindParam(":tournament_id", $this->tournament_id, PDO::PARAM_INT);
		$stmt->bindParam(":category_id", $this->category_id, PDO::PARAM_STR);
		$stmt->bindParam(":court_id", $this->court_id, PDO::PARAM_STR);
		$stmt->bindParam(":match_start_time", $this->match_start_time, PDO::PARAM_STR);
		$stmt->bindParam(":match_end_time", $this->match_end_time, PDO::PARAM_STR);
		$stmt->bindParam(":match_date", $this->match_date, PDO::PARAM_STR);
		//$stmt->bindParam(":id", $this->org_id, PDO::PARAM_INT);
		
		$stmt->execute();
		$rows = $stmt->fetch(PDO::FETCH_ASSOC);
		$data = [];
		if(is_array($rows) && count($rows)>0){

			//get tournament details
			$td_stmt=$this->conn->prepare("SELECT `tournament_name` FROM `tournament_details` WHERE `id` = (:id) ");
			$td_stmt->bindParam(":id", $rows['tournament_id'], PDO::PARAM_INT);
			$td_stmt->execute();
			$td_rows = $td_stmt->fetch(PDO::FETCH_ASSOC);
			
			//get category name
			$tscmt_stmt=$this->conn->prepare("SELECT `subcategory_name` FROM `tournament_subcategory_master_table` WHERE `id` = (:id) ");
			$tscmt_stmt->bindParam(":id", $rows['category_id'], PDO::PARAM_INT);
			$tscmt_stmt->execute();
			$tscmt_rows = $tscmt_stmt->fetch(PDO::FETCH_ASSOC);

			//get umpire details
			$tud_stmt=$this->conn->prepare("SELECT `firstname` as umpire_name FROM `tournament_umpire_details` WHERE `id` = (:id) ");
			$tud_stmt->bindParam(":id", $rows['match_umpire'], PDO::PARAM_INT);
			$tud_stmt->execute();
			$tud_rows = $tud_stmt->fetch(PDO::FETCH_ASSOC);

			//get player 1 details
			$p1_stmt=$this->conn->prepare("SELECT CONCAT(users.firstname,' ',users.lastname) as player1_name,profile_pics.pic_name FROM users LEFT JOIN profile_pics ON profile_pics.users_id=users.id WHERE users.id = (:id) ");
			$p1_stmt->bindParam(":id", $rows['player1_id'], PDO::PARAM_INT);
			$p1_stmt->execute();
			$p1_rows = $p1_stmt->fetch(PDO::FETCH_ASSOC);

			//get player 2 details
			$p2_stmt=$this->conn->prepare("SELECT CONCAT(users.firstname,' ',users.lastname) as player2_name,profile_pics.pic_name FROM users LEFT JOIN profile_pics ON profile_pics.users_id=users.id WHERE users.id = (:id) ");
			$p2_stmt->bindParam(":id", $rows['player2_id'], PDO::PARAM_INT);
			$p2_stmt->execute();
			$p2_rows = $p2_stmt->fetch(PDO::FETCH_ASSOC);

			//get match type details
			$mtd_stmt=$this->conn->prepare("SELECT `name` as match_type FROM match_types_master WHERE id = (:id) ");
			$mtd_stmt->bindParam(":id", $rows['match_type_id'], PDO::PARAM_INT);
			$mtd_stmt->execute();
			$mtd_rows = $mtd_stmt->fetch(PDO::FETCH_ASSOC);

			//get tournament knockout details
			$tk_stmt=$this->conn->prepare("SELECT point_types.name as point_type,tournament_knockouts.set_type_id,set_types.name as set_type,points_mins_per_set.points,points_mins_per_set.mins FROM tournament_knockouts 
											LEFT JOIN point_types ON tournament_knockouts.point_type_id = point_types.id 
											LEFT JOIN set_types ON set_types.id=tournament_knockouts.set_type_id 
											LEFT JOIN points_mins_per_set ON points_mins_per_set.id = tournament_knockouts.points_id
											WHERE tournament_knockouts.match_type_id = (:match_type_id) AND tournament_knockouts.tournament_details_id = (:tournament_details_id)");
			$tk_stmt->bindParam(":match_type_id", $rows['match_type_id'], PDO::PARAM_INT);
			$tk_stmt->bindParam(":tournament_details_id", $rows['tournament_id'], PDO::PARAM_INT);
			$tk_stmt->execute();
			$tk_rows = $tk_stmt->fetch(PDO::FETCH_ASSOC);
			
			$data = [
				'tournament_id'=>$rows['tournament_id'],
				'tournament_name'=>$td_rows['tournament_name'],
				'category_id'=>$rows['category_id'],
				'category_name'=>$tscmt_rows['subcategory_name'],
				'ump_id'=>$rows['match_umpire'],
				'ump_name'=>$tud_rows['umpire_name'],
				'court_id'=>$rows['court_id'],
				'match_id'=>$rows['match_id'],
				'match_start_time'=>date('h:i a',strtotime($rows['match_start_time'])),
				'match_end_time'=>date('h:i a',strtotime($rows['match_end_time'])),
				'player1_id'=>$rows['player1_id'],
				'player1_name'=>ucfirst($p1_rows['player1_name']),
				'player1_pic'=>'https://www.mysportsarena.com/sportsbook/views/profile/uploads/'.$p1_rows['pic_name'],
				'player2_id'=>$rows['player2_id'],
				'player2_name'=>ucfirst($p2_rows['player2_name']),
				'player2_pic'=>'https://www.mysportsarena.com/sportsbook/views/profile/uploads/'.$p2_rows['pic_name'],
				'match_league_no'=>$rows['match_league_no'],
				'match_type_id'=>$rows['match_type_id'],
				'match_type'=>$mtd_rows['match_type'],
				'point_type'=>$tk_rows['point_type'],
				'set_types_id'=>$tk_rows['set_type_id'],
				'set_type'=>$tk_rows['set_type'],
				'points'=>$tk_rows['points'],
				'mins'=>$tk_rows['mins']
				];
		}
		

		return $data;
	}
}



?>