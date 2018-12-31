<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$requests = $user->getusercharterrequests($_GET['id']);

	echo json_encode($requests);
?>