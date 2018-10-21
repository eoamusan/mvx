<?php
	session_start();

	include 'class.user.php';

	$user = new User();

	$offers = $user->getoffers();

	echo json_encode($offers);
?>