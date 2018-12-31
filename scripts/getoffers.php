<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$offers = $user->getoffers($_GET['id']);

	echo json_encode($offers);
?>