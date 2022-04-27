<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Content-type: application/json; charset=utf-8');

require_once("config.php");
require_once("message.php");
require_once("db_manager.php");

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


				case "Buildings":
					$table ="buildings";
					$order ="name";
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

		default:
			$message->setError(99, "unknown 'Action' command");
	}
}
$message->write_response();

?>