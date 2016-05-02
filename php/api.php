<?php

require_once('config.php');
require_once('db.php');

class Api {
	private $header;
	
	function __construct() {
		// TODO: support different environments
		$this->header = $_SERVER;
	}

	/**
	 * ensures a valid secret token. If the secret token is invalid,
	 * a message is sent to the client and further execution is aborted.
	 */	
	function ensureValidSecret() {
		global $CONFIG_SECRET;

		if ($this->header['HTTP_X_SECRET'] != $CONFIG_SECRET) {
			$wrapper = array();
			$command = array();
			$command[] = 'ownersay';
			$command[] = 'Communication error with backend: Invalid token.';
			$wrapper[] = $command;
			echo json_encode($wrapper);
			exit();
		}
	}

	function processData() {
		$body = file_get_contents('php://input');
		$data = json_decode($body);
		$cmd = $data[0];
		
		if ($cmd == "touch") {
			$wrapper = array();
			$command = array();
			$command[] = 'dialog';
			$command[] = 1234; // identifier
			$command[] = $data[1]; // agentid
			$command[] = 'Merit System';
			$command[] = "['Add Merit', 'Add Demerit', 'List']";
			$wrapper[] = $command;
			echo json_encode($wrapper);
		}
	}
}

$api = new Api();
$api->ensureValidSecret();
$api->processData();

$wrapper = array();
$res = array();
$res[] = 'ownersay';
$res[] = $_SERVER['HTTP_X_SECONDLIFE_OWNER_KEY'].'---'.json_encode($body);

$wrapper[] = $res;
$res = array();
$res[] = 'dialog';
$res[] = 123;
$res[] = 'c615d292-8d06-4d79-a059-7fa95d9822f7';
$res[] = 'Hallo';
$res[] = '["OK"]';
$wrapper[] = $res;



echo json_encode($wrapper);
?>
