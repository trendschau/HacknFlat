<?php

require("vendor/autoload.php");

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;

# configuration
$config = [
	'immoWebUrl' 		=> 'https://www.immobilienscout24.de/Suche/radius/wohnung-mieten?centerofsearchaddress%3DBerlin%253B%253BBerlin%2520Hauptbahnhof%253B%253B%253B%26numberofrooms%3D2.0-%26price%3D-1500.0%26livingspace%3D60.0-%26exclusioncriteria%3Dswapflat%26pricetype%3Drentpermonth%26geocoordinates%3D52.524742%253B13.3695626%253B7.0%26enteredFrom%3Dresult_list%26viewMode%3DMAP%23%2F%3FboundingBox%3D52.45439%252C13.177302%252C52.594969%252C13.561823',
	'alert_mails'		=> 'mail addresses separated by comma',
];
$config['immoJsonUrl']	= str_replace('/Suche/', '/Suche/controller/mapResults.go?searchUrl=/Suche/', $config['immoWebUrl']);


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

	# store cookie locally for next session
	$cookies = $page->getCookies();
	$cookieReese = $cookies->findOneBy('name', 'reese84');
	if ($cookieReese)
	{
		$res = storeCookie($cookieReese->getValue());
	}

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

	if(!empty($alertids))
	{
		sendAlert($alertids, $config['alert_mails']);
	}
}

# refresh page in ...
$sec = random_int(50, 80);
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
	die();	
}

function addLogEntry()
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immologs.json";

	$logdata 	= [];
	if(file_exists($localfile))
	{
		$logdata = file_get_contents($localfile);
		$logdata = json_decode($logdata,true);
	}
	date_default_timezone_set('Europe/Berlin');
	$logdata[] 		= date("Y-m-d H:i:s");
	$logdata		= json_encode($logdata);
	file_put_contents($localfile, $logdata);
}

function getLog()
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immologs.json";

	if(file_exists($localfile))
	{
		$localresults = file_get_contents($localfile);
		$localresults = json_decode($localresults,true);
		return $localresults;
	}
	return [];
}

function clearLog()
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immologs.json";
	unlink($localfile);
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
	$localfilefresh = getcwd() . DIRECTORY_SEPARATOR . "immoalertcookiefresh.json";
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immoalertcookie.json";
	if(file_exists($localfilefresh))
	{
		$localresults = file_get_contents($localfilefresh);
		$localresults = json_decode($localresults,true);
		unlink($localfilefresh);
		return $localresults['value'];
	}
	elseif(file_exists($localfile))
	{
		$localresults = file_get_contents($localfile);
		$localresults = json_decode($localresults,true);
		return $localresults['value'];
	}	
	return false;
}

function storeCookie($value)
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immoalertcookie.json";
	$localdata		= json_encode(['value' => $value]);
	file_put_contents($localfile, $localdata);	
}

function storeFreshCookie($value)
{
	$localfile 		= getcwd() . DIRECTORY_SEPARATOR . "immoalertcookiefresh.json";
	$localdata		= json_encode(['value' => $value]);
	file_put_contents($localfile, $localdata);	
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