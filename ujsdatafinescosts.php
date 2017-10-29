<?php

// UJS collections information parser
#require_once("SimpleHTMLDOM/simple_html_dom.php");

// connect to the database
// require_once("c:\Mikes Program Files\wamp\www\cjcDB.php");

$basedir = "fcdata/";
$csvfile = $basedir . "out.csv";
$tempfile = tempnam($basedir, "TMP"); 
unlink($csvfile);

foreach (range("A", "Z") as $letter)
{
	$dir = $basedir . $letter;
	if ($files = scandir($dir))
	{
		echo "Openning $dir \n with " . sizeof($files) . " files.";
		
		foreach ($files as $file) 
		{
            $temp = fopen($tempfile, 'w');
			set_time_limit(100);
			if ($file == "." || $file == "..")
				continue;
				
			//if ($file != "29.html")
			//	continue;
			
#			$html = file_get_html($basedir . $letter . DIRECTORY_SEPARATOR . $file);
            $page = new DOMDocument();
            libxml_use_internal_errors(true);
            $page->loadHTMLFile($basedir . $letter . DIRECTORY_SEPARATOR . $file);
			echo "\nFile: $letter" . DIRECTORY_SEPARATOR . $file;

			// grab the Defendant table
			$defendantTable = $page->getElementById("Defendant"); #$html->find('table',2);

			// now get each defendant into an array
			$defendants = $defendantTable->getElementsByTagName("tr");

			//now iterate over each child.  we skip the first row b/c it is the header row
            $first = TRUE;
			foreach ($defendants as $defendant)
			{
                if ($first)
                {
                  $first=FALSE;
                  continue;
                }
                
                
				$defendantInfos = $defendant->getElementsByTagName("td");

				$name = getName($defendantInfos->item(0));
                $address = getInfo($defendantInfos->item(1));
                $paymentPlanNumber = getInfo($defendantInfos->item(2));
                $status = getInfo($defendantInfos->item(3));
                $delinquentAmount = deDollar(getInfo($defendantInfos->item(4)));
                $delinquentTotalOwed = deDollar(getInfo($defendantInfos->item(5)));
                $delinquentMonthlyAmount = deDollar(getInfo($defendantInfos->item(6)));

                fputcsv($temp, array(trim($name[1]), $name[0], $address, $paymentPlanNumber, $status, $delinquentAmount, $delinquentTotalOwed, $delinquentMonthlyAmount));
#				insertIntoDatabase($name, $oldDocketNum, $docketNum, $FTA, $benchWarrantDate, $judgmentNumber, $judgmentDate, $judgmentAmount, $judgmentAgainstLast, $judgmentAgainstFirst);
				
				
/*				print "\nName: $name[1] $name[0]";
				print "\naddress: $address";
				print "\nPaymentPlan: $paymentPlanNumber";
				print "\nstatus: $status";
				print "\nDel amount: $delinquentAmount";
				print "\nDel Owed: $delinquentTotalOwed";
				print "\nDel Payment: $delinquentMonthlyAmount";
                exit();              
/**/
				
			}
            libxml_clear_errors();
            fclose($temp);
            // this was a work around to deal with memory issues I was getting.  Turns out the memory
            // errors weren't because I was writing a huge CSV--it was because I wasn't clearing the libxml
            // errors.  This doesn't really cause problems, so I'm not reversing my changes, but 
            // i really don't need this.  Really, I should just write to the main csv file.
            system('cat ' . $tempfile . " >> " . $csvfile);
		}
	}
}
unlink($tempfile);
    
// @return array with the last name and first name as the two elements
function getName($info)
{
	$regex = "/(.*),\s?(?:&nbsp;)?(.*)/";
	preg_match($regex,trim($info->textContent),$matches);
	array_shift($matches);
	return array_map('trim', $matches);
}

function getInfo($info)
{
   return trim($info->textContent);
}

function deDollar($amount)
{
    return preg_replace("/[\$,]/","",$amount);
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