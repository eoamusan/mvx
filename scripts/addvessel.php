<?php
	session_start();

	$data = json_decode(file_get_contents("php://input"));

	include 'class.user.php';

	$mvx = new User();

	$addvessel = $mvx->addvessel($data);

	echo json_encode($addvessel);
?>