<?php

class DB {
	private $db;
	
	function __construct() {
		global $CONFIG_DB_DSN, $CONFIG_DB_USER, $CONFIG_DB_PASSWORD;
		try {
			$this->db = new PDO($CONFIG_DB_DSN.';charset=utf8', $CONFIG_DB_USER, $CONFIG_DB_PASSWORD);
			// TODO: SET @@session.time_zone = "+00:00";
		} catch (PDOException $e) {
			die('Database connection failed: ' . $e->getMessage());
		}
	}
	

}
