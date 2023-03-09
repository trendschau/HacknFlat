<?php

require("vendor/autoload.php");

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;

# configuration
$config = [
	'immoWebUrl' 		=> 'https://www.immobilienscout24.de/Suche/radius/wohnung-mieten?centerofsearchaddress=Berlin%3B%3BBerlin%20Hauptbahnhof%20(Tief)%3B%3B%3B&numberofrooms=2.0-&price=0.0-1500.0&livingspace=60.0-&exclusioncriteria=swapflat&pricetype=calculatedtotalrent&geocoordinates=52.5246361%3B13.369861%3B7.0&enteredFrom=result_list&viewMode=MAP#/?boundingBox=52.430668%2C12.98534%2C52.618246%2C13.754382',
	'alert_mails'		=> 'your email here'
];

$urlparts 				= explode("?", $config['immoWebUrl']);
$jsonurl 				= str_replace('/Suche/', '/Suche/controller/mapResults.go?searchUrl=/Suche/', $urlparts[0]);
$config['immoJsonUrl']	= $jsonurl . '?' . urlencode($urlparts[1]);

# check if a fresh cookie has been posted from webform
$cookievalue = isset($_POST['cookievalue']) ? $_POST['cookievalue'] : false;
if($cookievalue)
{
	$cookievalue = trim(str_replace("reese84:", "", $cookievalue));
	$cookievalue = trim($cookievalue, '"');
}

# create browser
$browserFactory = new BrowserFactory();

# add random user agent
$user_agents = [
                'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.56 (KHTML, like Gecko) Version/9.0 Safari/601.1.56',
                'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.7 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.7',
                'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
                'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
                'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/5.0)',
            ];
$random_array_key = array_rand($user_agents);
$user_agent = $user_agents[$random_array_key];

# starts headless chrome
$browser = $browserFactory->createBrowser([
	'windowSize' => [1200, 780],
	'headless' => true,
	'userAgent' => $user_agent,
	'connectionDelay' => 3,
	'customFlags' => ['--incognito']
]);

try {

    # create a new page
    $page = $browser->createPage(); 

    # if there is no cookie value yet, get the locally stored cookie value from previous visit
    if(!$cookievalue)
    {
	    $cookievalue = getCookie();
    }

    # set cookie
    if($cookievalue)
    {
	    $page->setCookies([
		    Cookie::create('reese84', $cookievalue, [
		        'domain' => '.immobilienscout24.de',
		        'expires' => strtotime("+1 month")
	    	])
		])->await();
    }

    # navigate to the page, it returns JSON data if not blocked by captcha page
	$page->navigate($config['immoJsonUrl'])->waitForNavigation();

	$response = $page->getHtml();
	$result = trim(strip_tags($response));
	$result = json_decode($result, true);
	if($result === null)
	{
		# if we have no json result, then it is the blocker page, show form for fresh token
		showFormForToken();
	}
} finally {
    $browser->close();
}

$resultlist = isset($result['resultlist.resultlist']['resultlistEntries'][0]['resultlistEntry']) ? $result['resultlist.resultlist']['resultlistEntries'][0]['resultlistEntry'] : false;

if($resultlist)
{
	$oldresults = getLocalData();
	$newresults = [];
	$alertids = [];

	foreach($resultlist as $resultitem)
	{
		$newresults[$resultitem["@id"]] = true;

		if(!$oldresults OR !isset($oldresults[$resultitem["@id"]]))
		{
			$alertids[] = $resultitem["@id"];
		}
	}

	storeLocalData($newresults);

	# just to check:
	echo '<pre>';
	print_r($alertids);

	# send alerts
	if(!empty($alertids))
	{
		sendAlert($alertids, $config['alert_mails']);
	}
}

echo date("h:i:s");

# refresh page in ...
$sec = random_int(60, 80);
header("refresh:" . $sec . ";");

function showFormForToken()
{
	$htmlform = '<!DOCTYPE html>
					<html>
					<head>
					    <title>Token Value</title>
					    <meta charset="UTF-8">
					    <style>
					    	body {width: 100%; color:white; background:red; }
					        form {
					            margin: 30% auto;
					        }
					        input {
					            display: block;
					            margin: 10px 15px;
					            padding: 8px 10px;
					            font-size: 16px;
					        }
					    </style>
					</head>
					<body>
					    <form method="post">
					    	<h2>Refresh Cookie Value</h2>
					        <input type="text" name="cookievalue"  
					                           placeholder="Enter cookie Value">
					        <input type="submit" value="submit">
					    </form>
					    <br>
					</body>
					</html>';
	echo date("h:i:s");					
	echo $htmlform;
}

function getLocalData()
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immoalertresults.json";

	if(file_exists($localfile))
	{
		$localresults = file_get_contents($localfile);
		$localresults = json_decode($localresults,true);
		return $localresults;
	}
	return false;
}

function storeLocalData($data)
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immoalertresults.json";
	$localdata		= json_encode($data);
	file_put_contents($localfile, $localdata);
}

function getCookie()
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "reese84.txt";
	$files 			= glob("reese8*.txt");

	if(!empty($files))
	{
		arsort($files);
		$localresults = file_get_contents($files[0]);

		if(count($files) > 1)
		{
			$localresults = file_get_contents($files[1]);
		}

		$localresults = json_decode($localresults,true);
	}

	foreach ( $files as $filename) 
	{
		unlink($filename);
	}

	if(isset($localresults[0]['value']))
	{
		return $localresults[0]['value'];
	}

	return false;
}

function sendAlert($ids, $addresses)
{
	$message = "Hallo,\r\nauf Immoscout gibt es neue Inserate:\r\n";

	foreach($ids as $id)
	{
		$message .= "https://www.immobilienscout24.de/expose/" . $id . "\r\n";
	}

	mail($addresses, 'Neue Inserate auf Immoscout', $message);		
}