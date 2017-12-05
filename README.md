# U232-Uploader
This is a single category uploader bot for u232 code sites.  It was built for racing torrents to site.

## Requirements
* PHP
* Curl
* mktorrent

## Setup for scene axx
1. Activate your quick login option on u232 site.
2. Edit the directory paths, announce url with passkey, and q login.
3. Change the type number on line 103 to the category you want to up load to.($torrent_info['type']='9';)
4. If you're not using scene axx then I suggest setting up autotools.  In rutorrent plugin automove hardlink to your UPLOAD_PATH) then make MOVE_PATH 

## Setup for non scene axx
1. Activate your quick login option on u232 site.
2. Edit announce url with passkey, and q login.
3. In rutorrent setup automove plugin. You'll want to hardlink to point to your UPLOAD_PATH, then make MOVE_PATH a delete directory(don't sync this directory to your download directory).  Your TORRENT_PATH directory should be your rtorrent watch directory.
4. Change the type number on line 103 to the category you want to up load to.($torrent_info['type']='9';)
5. Setup cron job to delete all files and directories in your MOVE_PATH.

## How to use
This script was made to work best with rtorrent/rutorrent.  Before trying to upload anything run the script once so it can grab the cookie (the first login will fail).

## TODO
* Create error directory for failed uploads.
* Create a bot log file, writes things like "starting on XYZ... blah blah". Can be useful when daemonized.
* Multiple category for non site racing.
