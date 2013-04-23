<?php
if (!isset($argv[1]) or !isset($argv[2])) {
	echo "Usage: ".$argv[0]. " <CardNo.> <Password>\n";
	exit;
}

$loansURL = "https://www.londonlibraries.gov.uk/Richmond/01_YourAccount/01_002_YourLoans.aspx";
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
$logonAction = substr($loansURL, 0, strrpos($loansURL, '/')+1).$matches[1];

preg_match_all('/<input[^>]*>/', $data, $matches);
foreach ($matches[0] as $match) {
	preg_match('/name="([^"]*)"/', $match, $names);
	preg_match('/value="([^"]*)"/', $match, $values);
	$name = $names ? $names[1] : '';
	$value = $values ? $values[1] : '';

	if (strstr($name, '__') or strstr($name, 'PlaceCent')) {
		if (strstr($name, 'username')) {
			$value = $myusername;
		} else if (strstr($name, 'password')) {
			$value = $mypassword;
		}
		$postVars[$name] = $value;
	}
}

curl_setOpt($ch, CURLOPT_POST, TRUE);
curl_setOpt($ch, CURLOPT_POSTFIELDS, $postVars);
curl_setOpt($ch, CURLOPT_URL, $logonAction);

$data = curl_exec($ch);

$matches = array();
preg_match_all('/TitleListResultsCenterStyle4Reduced.*<h2><a[^>]*>([^<]*)<.*<h2>By ([^<]*)<\/h2>.*<strong>Due Date:<\/strong>(\d{1,2}\/\d{1,2}\/\d{2})/Us', $data, $matches);

for ($i = 0; $i < count($matches[0]); $i++) {
	$book = trim($matches[1][$i]);
	$author = trim($matches[2][$i]);
	$dueDate = date_create_from_format('d/m/y', $matches[3][$i]);
	$daysRemaining = -$dueDate->diff(new DateTime())->format('%r%a');
	printf("%s (%s) due back in %s days\n", $book, $author, $daysRemaining);
}

curl_close($ch);
