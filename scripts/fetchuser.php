<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$vessel = $user->fetch($_GET['id'], $_GET['src']);

	echo json_encode($vessel);
?>