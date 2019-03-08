<?php
	session_start();

	$data = json_decode(file_get_contents("php://input"));

	include 'class.user.php';

	$mvx = new User();

	$update = $mvx->update($data);

	echo json_encode($update);
?>