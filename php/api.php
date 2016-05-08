<?php

require_once('config.php');
require_once('db.php');
require_once('logic.php');

class Api {
	private $header;
	
	function __construct() {
		// TODO: support different environments
		$this->header = $_SERVER;
		if (!isset($this->header['X-SecondLife-Owner-Key'])) {
			$this->header['X-SecondLife-Owner-Key'] = $this->header['HTTP_X_SECONDLIFE_OWNER_KEY'];
		}
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

		try {
			$db = new DB();
			$eventHandler = new EventHandler($db);
			$res = $eventHandler->$cmd($this->header, $data);
			$db->commit();
			return $res;
		} catch (Exception $e) {
			return array(array('debug', $e->getMessage()), array('debug', $e->getTraceAsString()));
		}
	}
}

$api = new Api();
$api->ensureValidSecret();
$response = $api->processData();
echo json_encode($response);
