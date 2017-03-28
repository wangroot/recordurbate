#!/usr/bin/php7.0
<?php
//get configs and make copy
require_once 'config.php';
$streamers = $GLOBALS['streamers'];

//remove all disabled or already recording streamers and reindex
for($i = 0; $i < sizeof($streamers); $i++)
{
	if(!$streamers[$i]["enabled"] || $streamers[$i]["recording"])
	{
		unset($streamers[$i]);
	}
}
$streamers = array_values($streamers);

//get api page
$apiPage = json_decode(getURL($GLOBALS['general'][0]['apiURL']), true);

//go though each current streamer
for($currAPI = 0; $currAPI < sizeof($apiPage); $currAPI++)
{
	//go though each wanted streamer
	for($wanted = 0; $wanted < sizeof($streamers); $wanted++)
	{
		if($streamers[$wanted]["name"] === $apiPage[$currAPI]["username"])
		{
			echo $streamers[$wanted]["name"] . " is live";
			shell_exec('./record.php ' . $streamers[$wanted]["name"] . " > /dev/null 2>/dev/null &");
			break;
		}
	}
}

function getURL($url)
{
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($c);

	if(curl_error($c))
	{
		die(curl_error($c));
	}

	$status = curl_getinfo($c, CURLINFO_HTTP_CODE);

	curl_close($c);
	return $response;
}