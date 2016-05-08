<?php
require_once('config.php');
require_once('db.php');
require_once('logic.php');

$db = new DB();
$eventHandler = new EventHandler($db);
$header = array('X-SecondLife-Owner-Name' => 'legacyStudent', 'X-SecondLife-Owner-Key' => 'keyStudent');


// Rezzing of the merit object in world
$data = array('on_rez', 0, '0.1', 'keyStudent', 'legacyStudent', 'displayStudent');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";


echo "\r\n";



// Teacher touches merit system of student
$data = array('touch', 'keyTeacher', 'legacyTeacher', 'displayTeacher', 'groupTeacher');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";

// Teacher answer to main dialog with "Add Demerit"
$identifier = $res[0][1];
$data = array('listen', $identifier, 'keyTeacher', 'legacyTeacher', 'displayTeacher', 'groupTeacher', 'Add Demerit');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";

// Teacher states a reason for the demerit
$identifier = $res[0][1];
$data = array('listen', $identifier, 'keyTeacher', 'legacyTeacher', 'displayTeacher', 'groupTeacher', 'For taking over class and spreading misinformation.');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";


echo "\r\n";

// Teacher touches merit system of student
$data = array('touch', 'keyTeacher', 'legacyTeacher', 'displayTeacher', 'groupTeacher');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";

// Teacher answer to main dialog with "List"
$identifier = $res[0][1];
$data = array('listen', $identifier, 'keyTeacher', 'legacyTeacher', 'displayTeacher', 'groupTeacher', 'List');
$res = $eventHandler->$data[0]($header, $data);
echo json_encode($res) . "\r\n";

$db->commit();