<?php

class Utils {

	/**
	 * genitive s
	 *
	 * @param string $word word to convert into possessive genitive
	 * @return string
	 */
	static function genitive($word) {
		if ($word[strlen($word) - 1] != 's') {
			return $word."'s";
		} else {
			return $word."'";
		}
	}
}

/**
 * Handles events sent by the in-world script
 */
class EventHandler {
	private $db;

	function __construct($db) {
		$this->db = $db;
	}


	function changed($header, $data) {
		// do nothing
		$wrapper = array();
		return $wrapper;
	}


	/**
	 * handle listen event by dispatching to the appropriate ListenEventHandler based on session status
	 *
	 * @param map $header
	 * @param array $data
	 * @return array commands for the in world script
	 */
	function listen($header, $data) {
		$sessionIdentifier  = $data[1];
		$actingAgentKey     = $data[2];
		$actingAgentLegacy  = $data[3];
		$actingAgentDisplay = $data[4];
		$actingAgentGroup   = $data[5];
		$message            = $data[6];

		$targetAgentId = $this->db->getKnownAgentIdByKey($header['X-SecondLife-Owner-Key']);
		$actingAgentId = $this->db->getAgentId($actingAgentLegacy, $actingAgentKey, $actingAgentDisplay);
		$sessionData = $this->db->getAndDeleteSessionState($targetAgentId, $actingAgentId, $sessionIdentifier);
		$status = $sessionData[0];
		$details = $sessionData[1];

		// TODO: handle invalid session $identifier
		$listenEventHandler = new ListenEventHandler($this->db);
		return $listenEventHandler->$status($header, $data, $details, $targetAgentId, $actingAgentId, $actingAgentKey, $actingAgentLegacy, $actingAgentDisplay, $message);
	}


	function poll($header, $data) {
		$wrapper = array();
		// TODO: Reply with pending messages
		return $wrapper;
	}

	/**
	 * handle on_rez events by ensuring that the target agents exists in the database
	 *
	 * @param map $header
	 * @param array $data
	 * @return array commands for the in world script
	 */
	function on_rez($header, $data) {
		// TODO: version check

		$startParameter     = $data[1];
		$version            = $data[2];
		$targetAgentKey     = $data[3];
		$targetAgentLegacy  = $data[4];
		$targetAgentDisplay = $data[5];

		// register agent
		$targetAgentId = $this->db->getAgentId($targetAgentLegacy, $targetAgentKey, $targetAgentDisplay);

		$wrapper = array();
		$wrapper[] = array('setobjectname', Utils::genitive($targetAgentDisplay).' merit card');
		return $wrapper;
	}


	/**
	 * handle listener timeout
	 *
	 * @param map $header
	 * @param array $data
	 * @return array no commands for the in world script
	 */
	function timeout_listener($header, $data) {
		$identifier = $data[0];
		$targetAgentId = $this->db->getKnownAgentIdByKey($header['X-SecondLife-Owner-Key']);
		$this->db->deleteSessionState($targetAgentId, $identifier);
		return array();
	}


	/**
	 * handle touch event by displaying the top level dialog menu box
	 *
	 * @param map $header
	 * @param array $data
	 * @return array commands for the in world script
	 */
	function touch($header, $data) {
		$actingAgentKey     = $data[1];
		$actingAgentLegacy  = $data[2];
		$actingAgentDisplay = $data[3];
		$actingAgentGroup   = $data[4];

		$wrapper = array();
		
		$targetAgentId = $this->db->getKnownAgentIdByKey($header['X-SecondLife-Owner-Key']);
		$actingAgentId = $this->db->getAgentId($actingAgentLegacy, $actingAgentKey, $actingAgentDisplay);
		$identifier = $this->db->storeSessionState($targetAgentId, $actingAgentId, 'mainaction', '');

		$wrapper = array();
		$wrapper[] = array('dialog', $identifier, $actingAgentKey, 'Merit System', '["Add Merit", "Add Demerit", "List"]');
		return $wrapper;
	}
}


class ListenEventHandler {
	private $db;
	
	function __construct($db) {
		$this->db = $db;
	}

	/**
	 * handles a reply to the main dialog
	 *
	 * @param map $header
	 * @param array $data
	 * @param string $details session details
	 * @param int $targetAgentId database id of target agent
	 * @param int $actingAgentId database id of acting agent
	 * @param key $actingAgentKey SL key of acting agent
	 * @param string $actingAgentLegacy legacy name of acting agent
	 * @param string $actingAgentDisplay display name of acting agent
	 * @param string $message message
	 * @return array commands for the in world script
	 */
	function mainaction($header, $data, $details, $targetAgentId, $actingAgentId, $actingAgentKey, $actingAgentLegacy, $actingAgentDisplay, $message) {
		$wrapper = array();

		if ($message == 'Add Merit' || $message == 'Add Demerit') {
			$identifier = $this->db->storeSessionState($targetAgentId, $actingAgentId, 'add', $message);
			$wrapper[] = array('textbox', $identifier, $actingAgentKey, $message . ' by writing a short description of the reason.');

		} else if ($message == 'List') {
			$wrapper[] = array('say', 0, $actingAgentDisplay . ' ('.$actingAgentLegacy . ') checks merit card:');
			$merits = $this->db->getMerits($targetAgentId);
			$i = 1;
			foreach ($merits as $merit) {
				$wrapper[] = array('say', 0, $i . '. ' . $merit[2] . ' by ' . $merit[0]. ' (' . $merit[1] . '): ' . $merit[3]);
				$i++;
			}
		} else {
			$wrapper[] = array('say', 0, 'Internal Error: Unknown command "' . $message . '"');
		}
		return $wrapper;
	}



	/**
	 * handles a reply to the add merit/demerit text box
	 *
	 * @param map $header
	 * @param array $data
	 * @param string $details session details
	 * @param int $targetAgentId database id of target agent
	 * @param int $actingAgentId database id of acting agent
	 * @param key $actingAgentKey SL key of acting agent
	 * @param string $actingAgentLegacy legacy name of acting agent
	 * @param string $actingAgentDisplay display name of acting agent
	 * @param string $message message
	 * @return array commands for the in world script
	 */
	 function add($header, $data, $details, $targetAgentId, $actingAgentId, $actingAgentKey, $actingAgentLegacy, $actingAgentDisplay, $message) {
		$wrapper = array();
		$meritType = 'Merit';
		if ($details == 'Add Demerit') {
			$meritType = 'Demerit';
		}
		$this->db->insertMerit($targetAgentId, $actingAgentId, $meritType, $message);
		$wrapper[] = array('say', 0, $meritType . ' by ' . $actingAgentDisplay . ' (' . $actingAgentLegacy . '): ' . $message);
		return $wrapper;
	}
}