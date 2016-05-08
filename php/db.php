<?php

/**
 * low level database access
 */
class DB {
	private $db;

	function __construct() {
		global $CONFIG_DB_DSN, $CONFIG_DB_USER, $CONFIG_DB_PASSWORD;
		try {
			$this->db = new PDO($CONFIG_DB_DSN.';charset=utf8', $CONFIG_DB_USER, $CONFIG_DB_PASSWORD);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->exec('SET @@session.time_zone = "+00:00"');
			$this->db->beginTransaction();
		} catch (PDOException $e) {
			die('Database connection failed: ' . $e->getMessage());
		}
	}

	/**
	 * commits the database transaction
	 */
	function commit() {
		$this->db->commit();
	}

	/**
	 * gets the database id of a known agent
	 *
	 * @param key $keyId SL key of agent
	 * @return database id
	 */
	function getKnownAgentIdByKey($keyId) {
		$stmt = $this->db->prepare('SELECT id FROM agent WHERE keyid=:keyid');
		$stmt->execute(array(':keyid' => $keyId));
		return $stmt->fetchColumn();
	}

	/**
	 * gets the database id of an agent; created an entry if the agent is unknown up until now
	 *
	 * @param string $username    username of agent
	 * @param key $keyId          SL key of agent
	 * @param string $displayName display name of agent
	 * @return database id
	 */
	function getAgentId($username, $keyId, $displayName) {
		$stmt = $this->db->prepare('SELECT id, displayname FROM agent WHERE keyid=:keyid');
		$stmt->execute(array(':keyid' => $keyId));
		$data = $stmt->fetch();

		if ($data) {
			// Agent is already known. Update display name, if necessary.
			if ($data[1] != $displayName) {
				$stmt = $this->db->prepare("UPDATE agent SET displayname = :displayname WHERE id = :id");
				$stmt->execute(array(':id' => $data[0], ':displayname' => $displayName)); 
			}
			return $data[0];

		} else {

			// Agent is not known, insert
			$stmt = $this->db->prepare("INSERT INTO agent(keyid, username, displayname, role) VALUES (:keyid, :username, :displayname, '')");
			$stmt->execute(array(':keyid' => $keyId, ':username' => $username, ':displayname' => $displayName)); 
			return $this->db->lastInsertId();
		}
	}


	/**
	 * stores the session state
	 *
	 * @param int $targetAgentId  database id of target agent
	 * @param int $actingAgentId  database id of acting agent
	 * @param string $status      status
	 * @param string $details     details of the current status
	 * @return identifier
	 */
	function storeSessionState($targetAgentId, $actingAgentId, $status, $details) {
		$identifier = mt_rand(0, 0x7FFFFFFF);
		$stmt = $this->db->prepare('INSERT INTO session (target_agent_id, acting_agent_id, identifier, status, details)'
				                  .' VALUES (:target_agent_id, :acting_agent_id, :identifier, :status, :details)');
		$stmt->execute(array(':target_agent_id' => $targetAgentId, ':acting_agent_id' => $actingAgentId, 
							 ':identifier' => $identifier, ':status' => $status, ':details' => $details));
		return $identifier;
	}

	/**
	 * gets a session state and deletes it after reading
	 *
	 * @param int $targetAgentId  database id of target agent
	 * @param int $actingAgentId  database id of acting agent
	 * @param int $identifier     identifier of session state
	 * @return array: 0: status, 1 details
	 */
	function getAndDeleteSessionState($targetAgentId, $actingAgentId, $identifier) {
		$stmt = $this->db->prepare('SELECT status, details FROM session WHERE target_agent_id = :target_agent_id 
				                    AND acting_agent_id = :acting_agent_id AND identifier = :identifier ORDER BY id LIMIT 1');
		$stmt->execute(array(':target_agent_id' => $targetAgentId, ':acting_agent_id' => $actingAgentId, 
					   ':identifier' => $identifier));
		$res = $stmt->fetch();
		$this->deleteSessionState($targetAgentId, $identifier);
		return $res;
	}
	
	/**
	 * deletes a session state
	 *
	 * @param int $targetAgentId  database id of target agent
	 * @param int $identifier     identifier of session state
	 */
	function deleteSessionState($targetAgentId, $identifier) {
		$stmt = $this->db->prepare('DELETE FROM session WHERE target_agent_id = :target_agent_id AND identifier = :identifier ORDER BY id LIMIT 1');
		$stmt->execute(array(':target_agent_id' => $targetAgentId, ':identifier' => $identifier));
	}

	/**
	 * creates a transaction used to manage merits
	 *
	 * @param int $actingAgentId database id of acting agent
	 */
	function insertTransaction($actingAgentId) {
		$stmt = $this->db->prepare('INSERT INTO transaction (agent_id, transaction_timestamp) VALUES (:agent_id, CURRENT_TIMESTAMP())');
		$stmt->execute(array(':agent_id' => $actingAgentId));
		return $this->db->lastInsertId();		
	}

	/**
	 * inserts a merit or demerit
	 *
	 * @param int $targetAgentId  database id of target agent
	 * @param int $actingAgentId  database id of acting agent
	 * @param string $meritType   "merit" or "demerit"
	 * @param string $message     reason for the merit/demerit
	 */
	function insertMerit($targetAgentId, $actingAgentId, $meritType, $message) {
		$transaction = $this->insertTransaction($actingAgentId);
		$stmt = $this->db->prepare('INSERT INTO merit (target_agent_id, created_transaction_id, deleted_transaction_id, merit, message)'
				                            . ' VALUES (:target_agent_id, :created_transaction_id, 2, :merit, :message)');
		$stmt->execute(array(':target_agent_id' => $targetAgentId, ':created_transaction_id' => $transaction, 
				             ':merit' => $meritType, ':message' => $message));
	}


	/**
	 * get all merits/demerits for the specified agent
	 *
	 * @param int $targetAgentId  database id of target agent
	 */
	function getMerits($targetAgentId) {
		$stmt = $this->db->prepare('SELECT agent.displayname, agent.username, merit, message'
				                . ' FROM merit '
								. ' JOIN transaction AS t1 ON (merit.created_transaction_id=t1.id)'
				                . ' JOIN agent ON (agent.id = t1.agent_id)'
								. ' JOIN transaction AS t2 ON (merit.deleted_transaction_id=t2.id)'
								. ' WHERE t1.transaction_timestamp < CURRENT_TIMESTAMP()'
				                . ' AND CURRENT_TIMESTAMP() < t2.transaction_timestamp'
								. ' AND target_agent_id = :target_agent_id'
				);
		$stmt->execute(array(':target_agent_id' => $targetAgentId));
		return $stmt->fetchAll();
	}
}
