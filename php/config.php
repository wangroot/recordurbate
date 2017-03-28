<?php
$GLOBALS['general'] = json_decode(file_get_contents(__DIR__ . "/configs/general.json"), true);
$GLOBALS['streamers'] = json_decode(file_get_contents( __DIR__ . "/configs/streamers.json"), true);

function saveStreamers()
{
	file_put_contents(__DIR__ . "/configs/streamers.json", json_encode($GLOBALS['streamers']));
}