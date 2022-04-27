<?php 
require_once("error.php");

class RequestObject
{
	public $serverInfo;
	public $ip;
	public $request;
	public $requestFiles;
	public $cookie;
	public $session;

	public $action = null;
	public $data = null;
	public ?ErrorObject $error = null;

	function post_data(){    
		$data=explode('&',file_get_contents("php://input"));		
		$json = json_decode($data[0]);
		return $json;
	 }

	function __construct()
	{		
    	//$this->serverInfo = $_SERVER;
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->request = isset($_POST) && count($_POST) > 0 ? $this->post_data() : null;
		$this->requestFiles = isset($_FILES) && count($_FILES) > 0 ? $_FILES: null;
		$this->cookie = isset($_COOKIE) && count($_COOKIE) > 0 ? $_COOKIE : null;
		$this->session = isset($_SESSION) ? $_SESSION : null;

        if($this->request == null)
        {
            $this->error = new ErrorObject(2, "Request is empty");
            return;
        }

		
		if(isset($this->request->Action))
			$this->action = $this->request->Action;
		else
			$this->error = new ErrorObject(5, "No 'Action' in Request: ".json_encode($this->data));


		if(isset($this->request->Data))
			$this->data = $this->request->Data;
		else
			$this->error = new ErrorObject(6, "No 'Data' in Request: ".json_encode($this->data));
	}
}
?>