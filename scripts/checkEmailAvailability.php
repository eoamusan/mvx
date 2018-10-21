<?php
	session_start();

	$data = json_decode(file_get_contents("php://input"));

	include 'class.user.php';

	$user = new User();

	$bk_availability = $user->registrationExists($data->email);

	echo json_encode($bk_availability);
?>