# Recordurbate
> The act of recording a chaturbate livestream

## About
This is a bot/script to automatically record chaturbate live streams. 

## Requirements
- Some form of Linux machine
- PHP7.0-CLI
- PHP7.0-CURL
- Cron

Only PHP 7.0 has been tested. I can't guarantee it will work with any other versions. 

## Installation/Setup
Download/Copy the recordurbate.php file. Any directory should do as long as you can change the permissions.
```
sudo chmod +x recordurbate.php
sudo ./recordurbate.php setup
```
The setup will ask you for a save directory. Please ensure that what you enter has a trailing slash as seen in the examples.

## Usage
In the following commands, [NAME] should be replaced with the username of the streamer.

####Add

```
./recordurbate.php add [NAME]
```

####Remove

```
./recordurbate.php rm [NAME]
```

####Enabled

```
./recordurbate.php en [NAME]
```

####Disable

```
./recordurbate.php dis [NAME]
```

## TODO:
- Auto run FFMPEG when stream is over to "fix" mp4 file.
- Sanitation for user input, e.g. saveDir

Note: This entire thing probably should have been done in C++ or any other language but PHP was quick and easy.