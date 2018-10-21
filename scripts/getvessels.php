<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$vessels = $user->getvessels();

	echo json_encode($vessels);
?>