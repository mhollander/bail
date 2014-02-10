<?php

// UJS collections information parser
require_once("SimpleHTMLDOM/simple_html_dom.php");

// connect to the database
require_once("c:\Mikes Program Files\wamp\www\cjcDB.php");

$basedir = "c:\Mikes Program Files\wamp\www\collections\\";

foreach (range("R", "Z") as $letter)
{
	$dir = $basedir . $letter;
	if ($files = scandir($dir))
	{
		echo "Openning $dir <br/> with " . sizeof($files) . " files.";
		
		foreach ($files as $file) 
		{
			
			set_time_limit(100);

			if ($file == "." || $file == "..")
				continue;
			if ($letter == "R")
			{
				$a = explode(".",$file);
				if ($a > 99 || $a < 90)
					continue;
			}
		
			$html = file_get_html($basedir . $letter . "\\" . $file);
			
			echo "<br/>File: $letter \\ $file (" . date('h:i:s') . ")";
			ob_flush();
			flush();

			// grab the Defendant table
			$defendantTable = $html->find('table',2);

			// now get each defendant into an array
			$defendants = $defendantTable->childNodes();

			//now iterate over each child.  we start at #1 because the first row is the header row
			foreach (range(1, sizeof($defendants)-1) as $index)
			{
				$defendantInfos = $defendants[$index]->childNodes();
				$name = getName($defendantInfos[0]);
				
				$street = $street2 = $city = $state = $zip = "";
				
				list($street, , $street2, $city, $state, $zip) = getAddress($defendantInfos[1]);
			
				$paymentPlanNumber = getPaymentPlanNumber($defendantInfos[2]);
				$status = getStatus($defendantInfos[3]);
				$currentBalance = getBalance($defendantInfos[4]);

				insertIntoDatabase($name, $street, $street2, $city, $state, $zip, $paymentPlanNumber, $status, $currentBalance);
						
				/*
				print "<br />Name: $name[1] $name[0]";
				print "<br/>Address: $street<br/>$street2<br/>$city, $state $zip";
				print "<br/>PPN: $paymentPlanNumber";
				print "<br/>status: $status";
				print "<br/>Balance: $currentBalance";
				print "<br/><br/>";
				*/
			}
		}
	}
}

// @return array with the last name and first name as the two elements
function getName($info)
{
	$regex = "/(\w+),&nbsp;(\w+)/";
	preg_match($regex,trim($info->plaintext),$matches);
	array_shift($matches);
	return $matches;
}

// @return an array with address information - street, dummy, address2, city, state, zip
function getAddress($info)
{
	$regex = "/(.*)(\n(.*))?\n(.*),\s*(\w{2,2})\.&nbsp;(\d+)/";
	preg_match($regex,trim($info->plaintext), $matches);
	array_shift($matches);
	return $matches;
}

// @return a docket number in CPCMS style
function getPaymentPlanNumber($info)
{
	return trim($info->plaintext);
}
// @return a string representing an FTA date
function getStatus($info)
{
	return trim($info->plaintext);
}
// @return a string representing the bW date
function getBalance($info)
{
	$value = trim($info->plaintext);
	return trim($value,"$");
}


// inserts all of the data into the database
function insertIntoDatabase($name, $street, $street2, $city, $state, $zip, $paymentPlanNumber, $status, $currentBalance)
{
	$table = "collections_";
	
	// check to see if the defendant exists already
	$defID = checkDef($name, $street, $street2, $city, $state, $zip);
	
	// if not, insert the defendant into the table
	if (!isset($defID))
		$defID = insertDB("INSERT INTO " . $table . "def (`first`, `last`, `street`, `street2`, `city`, `state`, `zip`) values('" . mysql_real_escape_string($name[1]) . "', '" . mysql_real_escape_string($name[0]) . "', '" . mysql_real_escape_string($street) . "', '" . mysql_real_escape_string($street2) . "', '" . mysql_real_escape_string($city) . "', '" . mysql_real_escape_string($state) . "', '" . mysql_real_escape_string($zip) . "');");
		
	insertDB("INSERT INTO " . $table . "paymentplan (`defID`, `paymentPlanNumber`, `status`, `balance`) values('$defID', '" . mysql_real_escape_string($paymentPlanNumber) . "', '" . mysql_real_escape_string($status) . "', '"  . mysql_real_escape_string(str_replace(",","",$currentBalance)) . "');");

}

function insertDB($sql)
{
	//print "<br/>$sql";
	
	$result = mysql_query($sql, $GLOBALS['db']);
	if (!$result) 
			die('Could not add the arrest to the DB: [' . $sql . '] - ' . mysql_error());
	return mysql_insert_id();
	
}

// @return a date in the form YYYY-MM-DD
// @param a date in the form MM/DD/YYYY
function dateConvert($date)
{
	if (preg_match("/\d{1,2}\/\d{1,2}\/\d{2,4}/",$date))
	{
		$mysqlDate = new DateTime($date);
		return $mysqlDate->format('Y-m-d');
	}
	else
		return ("0000-00-00");
}

function checkDef ($name, $street, $street2, $city, $state, $zip)
{

	$sql = "SELECT id FROM collections_def WHERE first='" . mysql_real_escape_string($name[1]) . "' AND last='" . mysql_real_escape_string($name[0]) ."' AND street='" . mysql_real_escape_string($street) . "' AND street2='" . mysql_real_escape_string($street2) . "' AND city='" . mysql_real_escape_string($city) . "' AND state='" . mysql_real_escape_string($state) . "' AND zip='" . mysql_real_escape_string($zip) . "';";
	
	$result = mysql_query($sql, $GLOBALS['db']);
	if (!$result) 
			die('Could not query the def name from the DB:' . mysql_error());
	$row = mysql_fetch_row($result);
	return $row[0];

}


?>