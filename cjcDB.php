<?php

$dbPassword = "cjcData";
$dbUser = "cjcData";
$dbName = "bail";
$dbHost = "localhost";

$db = mysql_connect($dbHost, $dbUser, $dbPassword);
if (!$db)
	die('Could not connect: ' . mysql_error());
	
$db_selected = mysql_select_db($dbName, $db);
if (!$db_selected) 
	die ('Can\'t connect to database $dbName : ' . mysql_error());
	
?>