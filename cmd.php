<?php
require_once "php/config.php";

switch($_GET["action"])
{
	case "Add": addStreamer(); break;
	case "Enable": setEnDis(); break;
	case "Disable": setEnDis(); break;
	case "Delete": delete(); break;
	default: echo "WHAT DID YOU DO??!!"; break;
}

function setEnDis()
{
	$enabled = false;
	if($_GET["action"] == "Enable")
	{
		$enabled = true;
	}

	for($i = 0; $i < sizeof($GLOBALS["streamers"]); $i++)
	{
		if($GLOBALS["streamers"][$i]["name"] == $_GET['name'])
		{
			$GLOBALS["streamers"][$i]["enabled"] = $enabled;
			break;
		}
	}

	saveExit();
}

function delete()
{
	for($i = 0; $i < sizeof($GLOBALS["streamers"]); $i++)
	{
		if($GLOBALS["streamers"][$i]["name"] == $_GET["name"])
		{
			unset($GLOBALS["streamers"][$i]);
			break;
		}
	}

	$GLOBALS["streamers"] = array_values($GLOBALS["streamers"]);
	saveExit();
}

function addStreamer()
{
	$arr = [
		"name" => $_GET["name"],
		"enabled" => true,
		"recording" => false,
		"total" => 0,
		"last" => "never"
	];

	array_push($GLOBALS["streamers"], $arr);
	saveExit();
}

function saveExit()
{
	saveStreamers();
	header("Location: index.php");
}