<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$requests = $user->getcharterrequests();

	echo json_encode($requests);
?>