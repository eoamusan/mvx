<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$vessel = $user->getvessel($_GET['id']);

	echo json_encode($vessel);
?>