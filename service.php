<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Content-type: application/json; charset=utf-8');

require_once("config.php");
require_once("message.php");
require_once("db_manager.php");
require_once("quests.php");

$message = new Message();

function email($mail, $subject, $txt)
{
	$txt = wordwrap($txt,70);

	$txt."\n\nDein Katzenjammer Team";

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	
	// More headers
	$headers .= 'From: <webmaster@kleinemann.bplaced.net>' . "\r\n";

	mail($mail, $subject,$txt, $headers);
}


function _toMd5($txt)
{
	return md5(KEY.$txt.KEY);
}

if(isset($message->request))
{
	$req = $message->request;	
	switch($req->action)
	{
		case "login":
			$user = $req->data->User;
			$password = $req->data->Password;

			$dbRow = DB_Manager::getTable("SELECT * FROM user WHERE name ='$user';");

			if($dbRow->count != 1)
			{
				$message->setError(21, "Unknown User");
			}
			else
			{
				$dbRow = $dbRow->data[0];
				if($password == $dbRow['password'])
				{
					if($dbRow['status'] < 0)
					{
						$message->setError(23, "User is banned");
					}
					else
					{
						//Benutzer ist eingelogged
						session_start();
						$message->data['session'] = session_id();
						$message->data['id'] = $dbRow['id'];
						$message->data['name'] = $dbRow['name'];
						$message->data['home'] = $dbRow['home'];
						$message->data['money'] = $dbRow['money'];
						$message->data['guild'] = $dbRow['guild'];
					}
				}
				else
					$message->setError(22, "Wrong Password");
			}
			break;

		case "register":
			$email = $req->data->Email;
			$user = $req->data->User;
			$password = $req->data->Password;

			$dbRow = DB_Manager::getTable("SELECT * FROM user WHERE email ='$email' OR name ='$user';");
			if($dbRow->count > 0)
			{
				foreach($dbRow->data as $row => $rowValue)
				{
					if($rowValue['email'] === $email)
						$message->setError(31, "Email wird schon benutzt");

					if($rowValue['name'] === $user)
						$message->setError(32, "Benutzername ist schon vergeben");
				}
			}
			else
			{
				$insert = "INSERT INTO user (email, name, password) VALUES ('$email', '$user', '$password');";
				$dbInsert = DB_Manager::executeSql($insert);
				if($dbInsert->error != null)
					$message->setError(42, $dbInsert->error);
				else
				{
					$msg = "Willkommen $user\nDu hast die erfolgreich bei denm Spiel Katzenjammer registriert";

					email($email, "Katzenjammer Registrierung", $msg);

					$message->data["count"]	= $dbInsert->count;
				}			
			}
			break;

		case "select":
			$table = null;
			$columns = null;
			$where = null;
			$limit = null;
			$order = null;

			switch($req->data)
			{
				case "TopPlayer":
					$table = "user";
					$columns = "points, name, guild";
					$limit = 25;
					$order = "points desc";
					break;

				case "Updates":
					$table = "updates";
					$limit = 5;
					break;
				
				case "News":
					$table = "news";
					$limit = 5;
					$where = "(date_from IS NULL || date_from <= DATE((NOW())) && (date_to IS NULL || date_to >= DATE(NOW())))";
					break;

				case "Heroes":
					$table ="heroes";
					break;

				case "Buildings":
					$table ="buildings";
					break;

				case "BuildingHeroes":
						$table ="building_heroes";
						$order ="building_id";
						break;

				case "buildingQuests":
					$table ="buildings";
					break;

				case "Icons":
					$table ="icons";
					$order ="id";
					break;
				
				case "UserBuildings":
					$table ="user_buildings";
					$where ="user_id = ".$req->request->ID;
					break;

				case "UserHeroes":
						$table ="user_heroes";
						$where ="user_id = ".$req->request->ID;
						break;

				case "Quests":
					$table ="user_quest JOIN quests ON user_quest.quest_id = quests.id";
					$where ="user_building_id in (SELECT id FROM user_buildings WHERE user_id = ".$req->request->ID.")";
					break;

				default:
					$message->setError(98, "Select ist nicht definiert");
			}

			if($table == null)
				return;

			if($columns == null)
				$columns = "*";

			$sql = "SELECT $columns FROM $table";

			if($where != null)
				$sql .= " WHERE $where";

			if($order != null)
				$sql .= " ORDER BY $order";

			if($limit != null)
				$sql .= " LIMIT $limit";


			$sql .= ";";
			//echo print_r($sql);
			$message->data = DB_Manager::getTable($sql)->data;

			break;

		case "createQuest":
			//$building = $req->data->building_id;
			$message->data = createQuest($req->data);
			break;


		case "userUpdate":
				$data = $req->data;
				if(isset($data->home))
					$update = "UPDATE user SET home='".json_encode($data->home)."' WHERE id = $data->id";
				else
					$update = "UPDATE user SET guild=$data->guild WHERE id = $data->id";


				$update = DB_Manager::executeSql($update);
				if($update->error != null)
					$message->setError(42, $update->error);
				else
				{
					$message->data["count"]	= $update->count;
				}	
			break;


		case "buyBuilding":
			$data = $req->data;
			$user = $data->user_id;
			$pos = $data->position;
			$building = $data->building_id;
			
			$sqlBuilding = "SELECT * FROM buildings WHERE id = $building AND money <= (SELECT money FROM user where id = $user)";
		
			$dbRowBuilding = DB_Manager::getTable($sqlBuilding);
			if($dbRowBuilding->count == 1)
			{
				$insert = "INSERT INTO user_buildings (user_id, building_id, position) VALUES ($user, $building, '".json_encode($pos)."');";

				$money = $dbRowBuilding->data[0]['money'];
				$update = "UPDATE user SET money = money - $money WHERE id = $user;";

				$insert = DB_Manager::executeSql($insert);				
				$update = DB_Manager::executeSql($update);
				if($insert != null && $update != null)
				{
					$dbMoney = DB_Manager::getTable("SELECT money FROM user where id = $user");
					$dbBuilding = DB_Manager::getTable("SELECT * FROM user_Buildings where user_id = $user ORDER BY id desc LIMIT 1");
					$message->data['money'] = $dbMoney->data[0]['money'];
					$message->data['user_buildings'] = $dbBuilding->data[0];
				}
			}
			else
				$message->setError(101, "Das Gebäude ist zu teuer");

			break;

		case "buyHero":
			$data = $req->data;
			$user = $data->user_id;
			$hero = $data->hero_id;
			$building = $data->building_id;

			$sqlHero = "SELECT * FROM heroes WHERE id = $hero AND money <= (SELECT money FROM user where id = $user)";

			$dbRowHero = DB_Manager::getTable($sqlHero);

			if($dbRowHero->count == 1)
			{
				$insert = "INSERT INTO user_heroes (user_id, building_id, hero_id) VALUES ($user, $building, $hero);";

				$money = $dbRowHero->data[0]['money'];
				$update = "UPDATE user SET money = money - $money WHERE id = $user;";

				$insert = DB_Manager::executeSql($insert);				
				$update = DB_Manager::executeSql($update);
				if($insert != null && $update != null)
				{
					$dbMoney = DB_Manager::getTable("SELECT money FROM user where id = $user");
					$dbHero = DB_Manager::getTable("SELECT * FROM user_heroes where building_id = $building ORDER BY id desc LIMIT 1");
					$message->data['money'] = $dbMoney->data[0]['money'];
					$message->data['user_hero'] = $dbHero->data[0];
				}
			}
			else
				$message->setError(101, "Der Held ist zu teuer");
				
			break;

		default:
			$message->setError(99, "unknown 'Action' command");
	}
}
$message->write_response();

?>