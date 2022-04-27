<?php
require_once("error.php");
require_once("request.php");

class Message
{
	public $request;
	public $success = true;
	public $error = null;                           
	public $data = null;

	function __construct()
	{
		$request = new RequestObject();
		if($request->error == null)
		{
			$this->request = $request;
		}
		else
		{
			$this->setError($request->error->id, $request->error->errorText);
		}
	}

	function setError($id, $errorText)
	{
		$this->success = false;
		if($this->error == null)
			$this->error = array();

		array_push($this->error, new ErrorObject($id, $errorText));
	}

	function write_response()
	{
		print_r(json_encode($this));
	}
}
?>