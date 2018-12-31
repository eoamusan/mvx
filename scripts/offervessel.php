<?php
	session_start();

	$data = json_decode(file_get_contents("php://input"));

	include 'class.user.php';

	$mvx = new User();

	$chat = $mvx->offervessel($data);

	echo json_encode($chat);
?>