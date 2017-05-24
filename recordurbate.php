#!/usr/bin/php
<?php

if(!isset($argv[1]))
{
	printUsage();
	exit;
}

if($argv[1] != "setup")
{
	$_CONFIG = json_decode(file_get_contents("/etc/opt/recordurbate.json"), true);

	if($_CONFIG === false)
	{
		echo "Failed to get config, Setup first or permissions may have been changed\n";
		exit;
	}
}

switch($argv[1])
{
	case "add":
		addUser($argv[2]);
		break;
	case "rm":
		rmUser($argv[2]);
		break;
	case "en":
		setEnabled($argv[2], true);
		break;
	case "dis":
		setEnabled($argv[2], false);
		break;
	case "setup":
		setup();
		break;
	case "check":
		check();
		break;
	case "record":
		record($argv[2], $argv[3]);
		break;
	default:
		echo "Default";
		printUsage();
}

function addUser($name)
{
	global $_CONFIG;
	requireParam($name);

	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		if($_CONFIG["streamers"][$i]["name"] == $name)
		{
			echo "Streamer already added\n";
			exit;
		}
	}

	array_push($_CONFIG["streamers"], ["name" => $name, "enabled" => true, "recording" => false]);
	saveConfig();
	echo "$name has been added\n";
}

function rmUser($name)
{
	global $_CONFIG;
	requireParam($name);

	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		if($_CONFIG["streamers"][$i]["name"] == $name)
		{
			unset($_CONFIG["streamers"][$i]);
			$_CONFIG["streamers"] = array_values($_CONFIG["streamers"]);
			saveConfig();

			echo "$name has been removed\n";
			exit;
		}
	}

	echo "$name does not exist\n";
	exit;
}

function setEnabled($name, $enabled)
{
	global $_CONFIG;
	requireParam($name);

	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		if($_CONFIG["streamers"][$i]["name"] == $name)
		{
			$_CONFIG["streamers"][$i]["enabled"] = $enabled;
			saveConfig();
			echo "$name has been " . ($enabled ? "enabled" : "disabled") . "\n";
		}
	}
}

function setup()
{
	if(posix_getuid() != 0)
	{
		echo "Run as root\n";
		exit;
	}

	echo "Enter your save directory, relative or absolute\n";
	echo "Example, /home/oliver/videos/ or videos/\n";
	$saveDir = readline("Save Dir: ");

	echo "\nMaking config\n";
	$default =
		[
			"saveDir" => $saveDir,
			"streamers" => []
		];
	file_put_contents("/etc/opt/recordurbate.json", json_encode($default));
	chmod("/etc/opt/recordurbate.json", 0777);

	echo "Making cron\n";
	$cron = "* * * * * root php " . __FILE__ . " check > /dev/null 2>&1 &\n";
	file_put_contents("/etc/cron.d/recordurbate", $cron);

	echo "Done setting up\n\n";
}

function check()
{
	global $_CONFIG;

	if(sizeof($_CONFIG["streamers"]) == 0)
	{
		error_log("No streamers added");
		exit;
	} else if($_CONFIG["saveDir"] == "" || $_CONFIG["saveDir"] == null)
	{
		error_log("No save dir set");
		exit;
	}

	//remove all streamers disabled or currently recording and then reindex
	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		if(!$_CONFIG["streamers"][$i]["enabled"] || $_CONFIG["streamers"][$i]["recording"])
		{
			unset($_CONFIG["streamers"][$i]);
		}
	}
	$_CONFIG["streamers"] = array_values($_CONFIG["streamers"]);

	//go though each streamer, get info, check if live, start recording
	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		$name = $_CONFIG["streamers"][$i]["name"];
		$info = getInfo($name);

		if(!$info["success"])
		{
			error_log($name . " may not exist, check spelling, spaces, etc");
			continue;
		} else if($info["room_status"] != "public")
		{
			error_log($name . " is offline or in a private room");
			continue;
		}

		error_log($name . " is live, staring recording");

		$url = explode("playlist", $info["url"])[0];
		shell_exec("./recordurbate record $name $url > /dev/null 2>&1 &");
	}
}

function record($name, $url)
{
	global $_CONFIG;

	$saveDir = $_CONFIG["saveDir"] . "$name/";
	$date = date("Y-m-d");
	$file = $saveDir . "$date.tmp";

	$buffer = "";
	$bufferWait = 10;
	$bufferCount = 0;
	$live = true;
	$lastSegment = "";

	//make dir if not exists
	if(!file_exists($saveDir))
	{
		mkdir($saveDir, 0755);
	}

	//set live
	setRecording($name, true);
	error_log("Started recording $name");

	while($live)
	{
		//get chunklist, check if error or 404
		$chunklist = getURL($url . "chunklist.m3u8");

		if($chunklist === false)
		{
			$live = false;
			file_put_contents($file, $buffer, FILE_APPEND);
			continue;
		}

		//get last chunk
		$chunklist = explode("\n", $chunklist);

		//work out if already got
		$currSegment = $chunklist[sizeof($chunklist) - 2];
		$currSegmentNum = explode("_", explode(".", $currSegment)[0])[2];

		if($currSegmentNum == $lastSegment)
		{
			usleep(250000);
			continue;
		}

		//get chunk and check if 404
		$temp = getURL($url . $currSegment);

		if($temp === false)
		{
			$live = false;
			file_put_contents($file, $buffer, FILE_APPEND);
			continue;
		}

		$buffer .= $temp;
		$lastSegment = $currSegmentNum;

		if($bufferCount >= $bufferWait)
		{
			//file_put_contents($file, $buffer, FILE_APPEND);
			file_put_contents($file, $buffer, FILE_APPEND);

			$buffer = "";
			$bufferCount = 0;
		}

		$bufferCount++;
	}

	//set not live
	setRecording($name, false);
	error_log("Stopped recording $name");
}

function requireParam($param)
{
	if(!isset($param) || $param == "")
	{
		printUsage();
		exit;
	}
}

function printUsage()
{
	echo "Usage: recordurbate [OPTION] [NAME]\n";
	echo "Where  OPTION := { add | rm | en | dis }\n\n";
}

function saveConfig()
{
	global $_CONFIG;
	file_put_contents("/etc/opt/recordurbate.json", json_encode($_CONFIG));
}

function getInfo($name)
{
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, "https://chaturbate.com/get_edge_hls_url_ajax/");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "room_slug=" . $name . "&bandwidth=high");
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Requested-With: XMLHttpRequest"]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$info = json_decode(curl_exec($ch), true);
	curl_close($ch);

	return $info;
}

function getURL($url)
{
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	if(curl_errno($ch) != 0 || curl_getinfo($ch, CURLINFO_HTTP_CODE) == 404)
	{
		$response = false;
	}

	curl_close($ch);
	return $response;
}

function setRecording($name, $stat)
{
	global $_CONFIG;

	for($i = 0; $i < sizeof($_CONFIG["streamers"]); $i++)
	{
		if($_CONFIG["streamers"][$i]["name"] === $name)
		{
			$_CONFIG["streamers"][$i]["recording"] = $stat;
			break;
		}
	}
	saveConfig();
}