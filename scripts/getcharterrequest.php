<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$requests = $user->getcharterrequest($_GET['id']);

	echo json_encode($requests);
?>