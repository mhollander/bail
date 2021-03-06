<?php


$basedir = "fcdata/";
$baseURL = "http://www.courts.phila.gov/collections/index.asp?search=";

// @return true if the read was successful, false if there was an error
// @param url - the URL to read
// @param file - the file to write to
function readURLToFile($url, $file)
{
	print " <br />reading URL: $url";
	$ch = curl_init($url);
	$fp = fopen($file, "w");

	curl_setopt($ch, CURLOPT_FILE, $fp);

	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	
	// we want to stop when we get a webpage that doesn't have a next button.  This is the last page
	$html = file_get_contents($file);
	if (!preg_match("/\[Next/", $html))
	{
		print "<br />Got to end of the list.";
		return false;
	}
	
	return true;
}



foreach (range("A", "Z") as $letter)
{
	//if ($letter == "B")
	//	$i=259;
	//else
		$i = 1;
	
	if (!file_exists($basedir . DIRECTORY_SEPARATOR . $letter))
		mkdir($basedir . DIRECTORY_SEPARATOR . $letter);
		
	while ($i<10000)
	{
		$file = $basedir . DIRECTORY_SEPARATOR . $letter . DIRECTORY_SEPARATOR . $i . ".html";
        
        // if the file exists, then we have already downloaded it on a previous run and don't bother redownloading
        if (!file_exists($file))
        {
		    $url = $baseURL . $letter . "%25&searchfn=&page=" . $i;
		
    		// end the loop if and when we get to the webpage that doesn't include a "next" button
	    	if (!readURLToFile($url, $file))
		    	break;
        }
        
    	// set the timeout to 60 seconds per run of the readURL command
		set_time_limit(60);
		$i++;
	}
}


?>