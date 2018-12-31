<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$vessels = $user->getuservessels($_GET['id']);

	echo json_encode($vessels);
?>