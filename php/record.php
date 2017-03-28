#!/usr/bin/php7.0
<?php
require_once 'config.php';

$recName = $argv[1];
$saveURL = $GLOBALS['general'][0]['saveDir'] . $recName . "/" . time("Y-m-d");
$page = getURL("https://chaturbate.com/" . $recName . "/");

if($page === false)
{
	file_put_contents("log.txt", date("Y-m-d H:i:s") . ", Failed to find page for " . $recName, FILE_APPEND);
	exit;
}

//set streamer to recording
setRecording($recName, true);

//find base url
$start = strpos($page, "hlsSourceFast") + 17;
$pageMin = substr($page, $start);
$stop = strpos($pageMin, "playlist.m3u8");
$base = substr($pageMin, 0, $stop);

//set variables and start main loop
$lastGet = "";
$buffer = "";
$bufferWait = 10;
$bufferCount = 0;
$live = true;

while(true)
{
	//get chunk list and check if 404
	$chunklist = getURL($base . "chunklist.m3u8");
	if($chunklist === false)
	{
		//$live = false;
		echo "not live\n";
		continue;
	}

	//make into array
	$chunklist = explode("\n", $chunklist);

	//get chunk number
	$curr = $chunklist[sizeof($chunklist) - 2];
	$curr = substr($curr, strpos($curr, "_") + 1);
	$curr = substr($curr, strpos($curr, "_") + 1);
	$curr = substr($curr, 0, strlen($curr) - 3);

	//if already got, continue
	if($lastGet === $curr)
	{
		continue;
	}

	//set last get, download chunk and increase buff count
	$lastGet = $curr;
	$buffer .= getURL($base . $chunklist[sizeof($chunklist) - 2]);
	$bufferCount++;


	//write buffer if needed
	if($bufferCount >= $bufferWait)
	{
		file_put_contents($recName . ".mp4", $buffer, FILE_APPEND);
		echo "wrote buffer, " . strlen($buffer) . "\n";
		$buffer = "";
		$bufferCount = 0;
	}

	//sleep so we don't DDOS
	usleep(250000);
}

//set streamer to not recording
setRecording($recName, false);

function setRecording($name, $stat)
{
	for($i = 0; $i < sizeof($GLOBALS['streamers']); $i++)
	{
		if($GLOBALS['streamers'][$i]["name"] === $name)
		{
			$GLOBALS['streamers'][$i]["recording"] = $stat;
			break;
		}
	}
	saveStreamers();
}

function getURL($url)
{
	//set options and execute
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
	$response = curl_exec($c);

	//if failed print error
	if(curl_error($c))
	{
		die(curl_error($c));
	}

	//get HTTP code and close request
	$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
	curl_close($c);

	//if 404 return false
	if($status == 404)
	{
		return false;
	} else
	{
		return $response;
	}
}