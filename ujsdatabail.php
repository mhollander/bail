<?php

// UJS collections information parser
require_once("SimpleHTMLDOM/simple_html_dom.php");

// connect to the database
require_once("c:\Mikes Program Files\wamp\www\cjcDB.php");

$basedir = "c:\Mikes Program Files\wamp\www\defendantbail\\";

foreach (range("A", "Z") as $letter)
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
				
			//if ($file != "29.html")
			//	continue;
			
			$html = file_get_html($basedir . $letter . "\\" . $file);
			echo "<br/>File: $letter \\ $file";

			// grab the Defendant table
			$defendantTable = $html->find('table',2);

			// now get each defendant into an array
			$defendants = $defendantTable->childNodes();

			//now iterate over each child.  we start at #1 because the first row is the header row
			foreach (range(1, sizeof($defendants)-1) as $index)
			{
				$defendantInfos = $defendants[$index]->childNodes();
				
				$name = getName($defendantInfos[0]);
				$oldDocketNum = getOldDocketNumber($defendantInfos[1]);
				$docketNum = getCPCMSDocketNumber($defendantInfos[2]);
				$FTA = getFTA($defendantInfos[3]);
				$benchWarrantDate = getBenchWarrantDate($defendantInfos[4]);
				list($judgmentNumber, $judgmentDate, $judgmentAmount, $judgmentAgainstLast, $judgmentAgainstFirst) = getJudgmentInfo($defendantInfos[5]);
				
				insertIntoDatabase($name, $oldDocketNum, $docketNum, $FTA, $benchWarrantDate, $judgmentNumber, $judgmentDate, $judgmentAmount, $judgmentAgainstLast, $judgmentAgainstFirst);
				
				/*
				print "<br />Name: $name[1] $name[0]";
				print "<br/>OldNum: $oldDocketNum";
				print "<br/>CPCMS: $docketNum";
				print "<br/>FTA: $FTA";
				print "<br/>BWDate: $benchWarrantDate";
				print "<br/>JNum: $judgmentNumber";
				print "<br/>JDate: $judgmentDate";
				print "<br/>JAmount: $ $judgmentAmount";		
				print "<br/>JAagainst: $judgmentAgainstFirst $judgmentAgainstLast";
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

// @return a docketnumber
function getOldDocketNumber($info)
{
	return trim($info->plaintext);
}
// @return a docket number in CPCMS style
function getCPCMSDocketNumber($info)
{
	return trim($info->plaintext);
}
// @return a string representing an FTA date
function getFTA($info)
{
	return trim($info->plaintext);
}
// @return a string representing the bW date
function getBenchWarrantDate($info)
{
	return trim($info->plaintext);
}
// @returns an array with the judgment number, date, amount, and against information in an array
function getJudgmentInfo($info)
{
	//Number:&nbsp;7286388 Date:&nbsp;9/17/2001 Amount:&nbsp;$3,018.50
	$regex = "/Number:&nbsp;(\d+) Date:&nbsp;(.*) Amount:&nbsp;\\$(.*)\n.*\n(.*),&nbsp;(.*)?/";
	preg_match($regex, trim($info->plaintext), $matches);
	array_shift($matches);
	return $matches;
}

// inserts all of the data into the database
function insertIntoDatabase($name, $oldDocketNum, $docketNum, $FTA, $benchWarrantDate, $judgmentNumber, $judgmentDate, $judgmentAmount, $judgmentAgainstLast, $judgmentAgainstFirst)
{
	$table = "def_";
	
	$caseID = insertDB("INSERT INTO " . $table . "case (`CPCMS_Docket`, `Legacy_Docket`) values('" . mysql_real_escape_string($docketNum) . "', '" . mysql_real_escape_string($oldDocketNum) . "');");
	
	$defID = insertDB("INSERT INTO " . $table . "dname (`first`, `last`) values('" . mysql_real_escape_string($name[1]) . "', '" . mysql_real_escape_string($name[0]) ."');");
	
	$suretyID = insertDB("INSERT INTO " . $table . "sname (`first`, `last`) values('" . mysql_real_escape_string($judgmentAgainstFirst) . "', '" . mysql_real_escape_string($judgmentAgainstLast) . "');");
	
	insertDB("INSERT INTO " . $table . "judgment (`caseID`, `defID`, `suretyID`, `FTA`, `benchWarrantDate`, `judgmentNumber`, `judgmentDate`, `judgmentAmount`) values('$caseID', '$defID', '$suretyID', '" . dateConvert($FTA) . "', '" . dateConvert($benchWarrantDate) . "', '" . mysql_real_escape_string($judgmentNumber) . "', '" . dateConvert($judgmentDate) . "', '" . mysql_real_escape_string(str_replace(",","",$judgmentAmount)) . "');");

}

function insertDB($sql)
{
	//print "<br/>$sql";
	
	$result = mysql_query($sql, $GLOBALS['db']);
	if (!$result) 
			die('Could not add the arrest to the DB:' . mysql_error());
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


?>