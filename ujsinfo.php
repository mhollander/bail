<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<link rel="stylesheet" href="ujsStyle.css?reload" type="text/css" media="all" /> 
		<title>FJD Fines and Costs Information</title>
	</head>
<body>

<?php

// connect to the database
require_once("c:\Mikes Program Files\wamp\www\ujs\cjcDB.php");

// Defendant Bail information
print "<b>Defendant Bail Information</b>";
print "<ul>";

$sql = "SELECT COUNT(*) FROM def_judgment";
$totalJudgments = getSingleDBResult($sql);
echo sprintf("<li>There are a total of <span class='info'> %s bail judgments </span>recorded by the court.</li>",  number_format($totalJudgments));

$sql = "SELECT SUM(judgmentAmount) FROM def_judgment";
$totalValue = getSingleDBResult($sql);
echo sprintf("<li>There is a total of <span class='info'>$%s </span>in bail judgments owed to the court.</li>", number_format($totalValue,2));

$sql = "SELECT AVG(judgmentAmount) FROM def_judgment";
echo sprintf("<li>The average bail judgment is for <span class='info'>$%s.</span></li>", number_format(getSingleDBResult($sql),2));

$sql = "SELECT judgmentAmount FROM def_judgment ORDER BY judgmentAmount ASC LIMIT " . round($totalJudgments/2) . ",1";
echo sprintf("<li>The median bail judgment is for <span class='info'>$%s.</span></li>", number_format(getSingleDBResult($sql),2));

printJudgmentBeforeYear("2005", $totalJudgments, $totalValue);
printJudgmentBeforeYear("2000", $totalJudgments, $totalValue);
printJudgmentBeforeYear("1995", $totalJudgments, $totalValue);
printJudgmentBeforeYear("1990", $totalJudgments, $totalValue);

printZeroWaiting($totalJudgments, $totalValue);
printCompareDatesInterval('benchWarrantDate', 'FTA', "0", "60", 90, $totalJudgments, $totalValue);
printCompareDatesInterval('benchWarrantDate', 'FTA', "61", "90", 70, $totalJudgments, $totalValue);
printCompareDatesInterval('benchWarrantDate', 'FTA', "91", "120", 50, $totalJudgments, $totalValue);
printCompareDatesInterval('benchWarrantDate', 'FTA', "121", "180", 30, $totalJudgments, $totalValue);
print "</ul>";

print "<b>Bail judgments by year</b>";
print "<ul>";
printBailJudgmentsByYear(1970, $totalJudgments, $totalValue);
print "</ul>";


print "<b>Payment Plans/Collections Information</b>";
print "<ul>";

$sql = "SELECT COUNT(*) FROM collections_paymentplan";
$totalPPs = getSingleDBResult($sql);
echo sprintf("<li>There are a total of <span class='info'> %s payment plans </span>tracked by the court.</li>",  number_format($totalPPs));

$sql = "SELECT COUNT(*) FROM collections_def";
$totalPPDefs = getSingleDBResult($sql);
echo sprintf("<li>There are a total of <span class='info'> %s defendants </span>on those payment plans.</li>",  number_format($totalPPDefs));

$sql = "SELECT SUM(balance) FROM collections_paymentplan";
$totalValue = getSingleDBResult($sql);
echo sprintf("<li>There is a total of <span class='info'>$%s </span>in payment plans set up and owed to the court.</li>", number_format($totalValue,2));

$sql = "SELECT AVG(balance) FROM collections_paymentplan";
echo sprintf("<li>The average payment plan is for <span class='info'>$%s.</span></li>", number_format(getSingleDBResult($sql),2));

$sql = "SELECT balance FROM collections_paymentplan ORDER BY balance ASC LIMIT " . round($totalPPs/2) . ",1";
echo sprintf("<li>The median payment plan is for <span class='info'>$%s.</span></li>", number_format(getSingleDBResult($sql),2));

$sql = "SELECT COUNT(*) FROM collections_paymentplan WHERE status='Active'";
$active = getSingleDBResult($sql);
$sql = "SELECT COUNT(*) FROM collections_paymentplan WHERE status='Referred'";
$referred = getSingleDBResult($sql);
$sql = "SELECT COUNT(*) FROM collections_paymentplan WHERE status='Inactive'";
$inactive = getSingleDBResult($sql);
echo sprintf("<li>There are a total of <span class='info'>%s active </span>payment plans, <span class='info'>%s referred to collections </span>payment plans, and <span class='info'>%s inactive </span>payment plans.</li>", number_format($active), number_format($referred), number_format($inactive));

/* 
printJudgmentBeforeYear("2005", $totalJudgments, $totalValue);
printJudgmentBeforeYear("2000", $totalJudgments, $totalValue);
printJudgmentBeforeYear("1995", $totalJudgments, $totalValue);
printJudgmentBeforeYear("1990", $totalJudgments, $totalValue);
*/

print "</ul>";




// useful for sending select statements with single return value (like SELECT COUNT(*)....)
function getSingleDBResult($sql)
{
	//print "<br/>$sql";
	$result = mysql_query($sql, $GLOBALS['db']);
	if (!$result) 
			die('Could not run the query [$sql]:' . mysql_error());
	
	$row = mysql_fetch_row($result);
	return $row[0];
}

// useful for sending select statements with array-type return values (like SELECT *....)
function getDBResult($sql)
{
	//print "<br/>$sql";
	$result = mysql_query($sql, $GLOBALS['db']);
	if (!$result) 
			die('Could not run the query [' . $sql . ']:' . mysql_error());
	
	return mysql_fetch_assoc($result);
}

function printJudgmentBeforeYear($year, $totalJudgments, $totalValue)
{
	$sql = "SELECT COUNT(*) FROM  `def_judgment` WHERE  `FTA` <  '$year-01-01'";
	$numBeforeYear = getSingleDBResult($sql);
	$sql = "SELECT SUM(judgmentAmount) FROM  `def_judgment` WHERE  `FTA` <  '$year-01-01'";
	$valBeforeYear = getSingleDBResult($sql);
	echo sprintf("<li>Of these bail judgments, <span class='info'>%s (%s%%) </span>are from before <span class='info'>$year</span>, for a value of <span class='info'>$%s (%s%%)</span>.</li>", number_format($numBeforeYear), round(100*$numBeforeYear/$totalJudgments,2), number_format($valBeforeYear, 2), round(100*$valBeforeYear/$totalValue,2));
}

function printCompareDatesInterval($column1, $column2, $interval1, $interval2, $reduction, $totalJudgments, $totalValue)
{
	$sql = "SELECT COUNT(*) FROM `def_judgment` WHERE `$column1` <= `$column2` + INTERVAL $interval2 DAY AND `$column1` > `$column2` + INTERVAL $interval1 DAY";
	$numInterval = getSingleDBResult($sql);
	$sql = "SELECT SUM(judgmentAmount) FROM `def_judgment` WHERE `$column1` <= `$column2` + INTERVAL $interval2 DAY AND `$column1` > `$column2` + INTERVAL $interval1 DAY";
	$valInterval = getSingleDBResult($sql);

	echo sprintf("<li>Of these bail judgments, <span class='info'>%s (%s%%) </span>have a bench warrant date <span class='info'>$interval1 to $interval2 days</span> after the FTA date, for a total value of <span class='info'>$%s ($%s at a $reduction%% reduction)</span>.</li>", number_format($numInterval), round(100*$numInterval/$totalJudgments,2), number_format($valInterval, 2), number_format($reduction*$valInterval/100,2));
}

function printBailJudgmentsByYear($year, $totalJudgments, $totalValue)
{
	foreach (range($year, 2006) as $y)
	{
		$x = $y+1;
		$numYear = getSingleDBResult("SELECT COUNT(*) FROM `def_judgment` WHERE `FTA` < '$x-01-01' AND `FTA` >= '$y-01-01'");
		$valYear =  getSingleDBResult("SELECT SUM(judgmentAmount) FROM `def_judgment` WHERE `FTA` < '$x-01-01' AND `FTA` >= '$y-01-01'");
		
		echo sprintf("<li><span class='info'>$y</span>: %s (%s%%) bail judgments; $%s (%s%%); average judgment: $%s</li>", number_format($numYear),  round(100*$numYear/$totalJudgments,2), number_format($valYear,2), round(100*$valYear/$totalValue,2), number_format($valYear/$numYear,2));
		//print "<li>$y|" . number_format($numYear) . "|" .  round(100*$numYear/$totalJudgments,2). "|" . number_format($valYear,2). "|" . round(100*$valYear/$totalValue,2). "|" . number_format($valYear/$numYear,2) . "</li>";
	}
	
	$numYear = getSingleDBResult("SELECT COUNT(*) FROM `def_judgment` WHERE `FTA` = '0000-00-00'");
	$valYear =  getSingleDBResult("SELECT SUM(judgmentAmount) FROM `def_judgment` WHERE `FTA` = '0000-00-00'");
	
	echo sprintf("<li><span class='info'>$y</span>: %s (%s%%) bail judgments; $%s (%s%%); average judgment: $%s</li>", number_format($numYear),  round(100*$numYear/$totalJudgments,2), number_format($valYear,2), round(100*$valYear/$totalValue,2), number_format($valYear/$numYear,2));
	//print "<li>0000-00-00|" . number_format($numYear) . "|" .  round(100*$numYear/$totalJudgments,2). "|" . number_format($valYear,2). "|" . round(100*$valYear/$totalValue,2). "|" . number_format($valYear/$numYear,2) . "</li>";
}


function printZeroWaiting ($totalJudgments, $totalValue)
{
	$sql = "SELECT COUNT(*) FROM `def_judgment` WHERE `benchWarrantDate` = `FTA`";
	$numInterval = getSingleDBResult($sql);
	$sql = "SELECT SUM(judgmentAmount) FROM `def_judgment` WHERE `benchWarrantDate` = `FTA`";
	$valInterval = getSingleDBResult($sql);

	echo sprintf("<li>Of these bail judgments, <span class='info'>%s (%s%%) </span>have a bench warrant date and FTA date that are <span class='info'>equal</span>, for a total value of <span class='info'>$%s</span>.</li>", number_format($numInterval), round(100*$numInterval/$totalJudgments,2), number_format($valInterval, 2));
}


?>
</body></html>