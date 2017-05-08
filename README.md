# Recordurbate
The act of recording a chaturbate livestream

### About
This is a bot/script/thing to automatically record chaturbate live streams. 
- Code: Oliver Rose
- CSS: [Spectre.css](https://github.com/picturepan2/spectre)

### Requirements
- Some form of Linux machine
- Apache2
- PHP (only tested on version 7.0) with exec enabled
- CURL with CA bundle setup

### Installation
Copy all files to the web directoy, add cron job to call startRecord.php every interval (e.g. 1 minute). Ensure that the directory has read and write permissions. Also startRecord.php and record.php need execute permission.

### TODO:
- Automate cron job install and edit
- Auto run FFMPEG when stream is over to "fix" mp4 file.
- More options for configurations
