<?php

$body = file_get_contents('php://input');
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
