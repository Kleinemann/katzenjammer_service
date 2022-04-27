<?php
class ErrorObject
{
	public $id;
	public $errorText;
	
	function __construct($id, $errorText)
	{
		$this->id = $id;
		$this->errorText = $errorText;
	}
}
?>