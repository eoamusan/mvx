<?php
	session_start();

	$data = json_decode(file_get_contents("php://input"));

	include 'class.user.php';

	$mvx = new User();

	$resend = $mvx->resend($data->email);

	echo json_encode($resend);
?>