<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$chat = $user->getchatdetails($_GET['id']);

	echo json_encode($chat);
?>