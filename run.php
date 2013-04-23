<?php
if (!isset($argv[1]) or !isset($argv[2])) {
	echo "Usage: ".$argv[0]. " <CardNo.> <Password>\n";
	exit;
}

$loansURL = "https://richmond.spydus.co.uk/cgi-bin/spydus.exe/MSGTRN/OPAC/HOME";
$root = substr($loansURL, 0, strpos($loansURL, '/', 15));
$myusername = $argv[1];
$mypassword = $argv[2];
$postVars = array();
$cookieJar = './cookies.txt';
if (file_exists($cookieJar)) {
	unlink($cookieJar);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loansURL);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
$data=curl_exec($ch);

preg_match('/<form.*action="([^"]*)".*>/', $data, $matches);
$logonAction = $root.$matches[1];

preg_match_all('/<input[^>]*>/', $data, $matches);
foreach ($matches[0] as $match) {
	preg_match('/name="([^"]*)"/', $match, $names);
	preg_match('/value="([^"]*)"/', $match, $values);
	$name = $names ? $names[1] : '';
	$value = $values ? $values[1] : '';

	if (strstr($name, 'BRW') or strstr($name, 'ISGLB')) {
		if (strstr($name, 'BRWLID')) {
			$value = $myusername;
		} else if (strstr($name, 'BRWLPWD')) {
			$value = $mypassword;
		} else if (strstr($name, 'ISGLB')) {
			$value = "1";
		}
		$postVars[] = $name.'='.$value;
	}
}
curl_setOpt($ch, CURLOPT_POST, TRUE);
curl_setOpt($ch, CURLOPT_POSTFIELDS, implode($postVars, '&'));
curl_setOpt($ch, CURLOPT_URL, $logonAction);

$data = curl_exec($ch);
$matches = array();
preg_match('/url=(.*)"/', $data, $matches);

curl_setOpt($ch, CURLOPT_URL, $matches[1]);
curl_setOpt($ch, CURLOPT_POST, FALSE);
$data = curl_exec($ch);

$matches = array();
preg_match('/<a href="([^<]*)">Current loans<\/a>/', $data, $matches);

$query = explode('?', $matches[1]);
curl_setOpt($ch, CURLOPT_URL, $root.str_replace("&amp;", "&", $matches[1]));
$data=curl_exec($ch);

//echo $data;

$matches = array();
preg_match_all('/<tr valign="top">.*?<td.*?\/td><td.*?><a.*?>(.*?)<\/a>.*?<\/td><td.*?>(.*?)<.*?<\/tr>/', $data, $matches);

for ($i = 0; $i < count($matches[0]); $i++) {
	$book = trim($matches[1][$i]);
	$dueDate = date_create_from_format('d M Y', $matches[2][$i]);
	$daysRemaining = -$dueDate->diff(new DateTime())->format('%r%a');
	printf("'%s' due back in %s days\n", $book, $daysRemaining);
}

curl_close($ch);
