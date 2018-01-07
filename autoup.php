<?php
define('ROOT_PATH', '/home/upload');
define('UPLOAD_PATH', ROOT_PATH.'/scan');
define('MOVE_PATH', ROOT_PATH.'/move');
define('ERROR_PATH', ROOT_PATH.'/error');
define('TORRENT_PATH', ROOT_PATH.'/torrent');
define('TEMP_TORRENT', ROOT_PATH.'/temp');

define('LOG_FILE', ROOT_PATH.'/bot.log');
define('JOB_LOG', ROOT_PATH.'/jobs');

define('SITE_ROOT', 'https://test.site');
define('ANNOUNCE_URL', 'https://test.site/announce.php');
define('Q_LOGIN', 'https://test.site/pagelogin.php?qlogin=176a4490e19018f0f2ff65526f6ca6dcb5dc8128199656bcc8b2f39c06840f7afb41054c629efe6c156b97c85674a40d');

function move($source, $dest)
{
	$cmd = 'mv "'.$source.'" "'.$dest.'"'; 
	exec($cmd, $output, $return_val); 
	if ($return_val == 0) return 1;
	return 0;
}

function make_login()
{
	$login_url = Q_LOGIN;
	$ch = curl_init($login_url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	$rez = curl_exec($ch);
	if (!$rez) die('Cannot login!');
	echo file_put_contents(LOG_FILE, 'Cannot login! '.date('m/d/Y h:i:s')."\r\n", FILE_APPEND);
}

function make_torrent($file)
{
	$info = pathinfo($file);
	$output = TEMP_TORRENT.'/'.$info['basename'].'.torrent';
	if (file_exists($output)) unlink($output);
	$cmd = "mktorrent '$file' -o '$output' -a ".ANNOUNCE_URL;
	exec($cmd);
	if (file_exists($output)) return $output;
	else die('Cannot make torrent!');
	echo file_put_contents(LOG_FILE, "Cannot make $file torrent! ".date('m/d/Y h:i:s')."\r\n", FILE_APPEND);
}

function make_upload($file_full, $ext, $new_dir)
{
	$file = pathinfo($file_full, PATHINFO_BASENAME);
	$file_without_ext = pathinfo($file_full, PATHINFO_FILENAME);
	
	$move_file = $new_dir.'/'.$file;
	$nfo_file = $new_dir.'/'.$file;
			
	$rez = move($file_full, $move_file);
	if (!$rez) die('Cannot move file!');
	$torrent = make_torrent($move_file);
	
        $source = glob($nfo_file.'/*.nfo');
	
	$nfo = 'There was no nfo file found!';
	foreach ($source as $a) {
	if (substr(strtolower($a), -4) == '.nfo') {
	$nfo = file_get_contents($a);
	$match = array("/[^a-zA-Z0-9-._&=?:'\/\s]/", "/\s{2,}/");
	$replace = array("", " ");
	$nfo = preg_replace($match, $replace, trim($nfo));
	}
	}

        $imdb = "";
    	if (preg_match('/http:\/\/www.imdb.com\/title\/tt[\d]+\//', $nfo, $matches)) 
	{
        $imdb = $matches[0];
	}
	
	switch(true) {
	case preg_match('/hdtv|sdtv|pdtv|tvrip/i', $file) : $cat = 5; break;
	case preg_match('/xvid|brrip|dvdrip|hdrip/i', $file) : $cat = 10; break;
	case preg_match('/x86|x64|win64|lnx64|macosx/i', $file) : $cat = 1; break;
	case preg_match('/wii|wiiu|xbox|xbox360|ps3|ps4/i', $file) : $cat = 2; break;
	case preg_match('/dvdr/i', $file) : $cat = 3; break;
	case preg_match('/mp3|flac|lossless|cd|compilation|album|albums|vinyl/i', $file) : $cat = 4; break;
	case preg_match('/xxx/i', $file) : $cat = 6; break;
	case preg_match('/psp/i', $file) : $cat = 7; break;
	case preg_match('/ps2/i', $file) : $cat = 8; break;
	case preg_match('/anime/i', $file) : $cat = 9; break;
	case preg_match('/720p|1080p/i', $file) : $cat = 11; break;
	case preg_match('/pc/i', $file) : $cat = 12; break;
	default : $cat = 9;
	}

	$torrent_info = Array();
	$torrent_info['name'] = $file;
	$torrent_info['descr'] = $nfo;
	$torrent_info['url'] = $imdb;
	$torrent_info['type']= $cat;
	upload_torrent($torrent, $torrent_info, $file);
}

function test_login()
{
	$login_url = SITE_ROOT.'/mytorrents.php';
	$ch = curl_init($login_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);
	if (!$rez) make_login();
}

function upload_torrent($torrent, $torrent_info, $file)
{
	loged_in:
	$upload_url = SITE_ROOT.'/upload.php';
	$ch = curl_init($upload_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);
	
        $torrent_info['MAX_FILE_SIZE']=3145728;
	$torrent_info['poster']='';	
	$torrent_info['youtube']='';	
	$torrent_info['file'] = new CURLFile (TEMP_TORRENT.'/'.$file .".torrent");	
	$torrent_info['description']='Auto Upload Bot';	
	$torrent_info['fontfont']='0';
	$torrent_info['fontsize']='0';
	$torrent_info['request']='0';
	$torrent_info['release_group']='none';
	$torrent_info['strip']=	'strip';
	
        $fh = fopen(JOB_LOG.'/'.$file, 'a') or die;
	$string_data = "Name: ".$torrent_info['name'].PHP_EOL."Added: ".date("m/d/Y h:i:s").PHP_EOL."NFO: ".$torrent_info['descr']
	.PHP_EOL."IMDB: ".$torrent_info['url'].PHP_EOL."Category: ".$torrent_info['type'];
	fwrite($fh, $string_data);
	fclose($fh);
	
        $upload_url = SITE_ROOT.'/takeupload.php';
	$ch = curl_init($upload_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HEADER, 1); 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $torrent_info);
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);
	if (!$rez || strpos($rez, 'login.php')) 
	{
		make_login();
		goto loged_in;
	}

	unlink(TEMP_TORRENT.'/'.$file.".torrent");

	strpos($rez,'Upload failed!') ? file_put_contents(LOG_FILE, "$file failed on ".date("m/d/Y h:i:s")."\r\n", FILE_APPEND) && move(MOVE_PATH.'/'.$file, ERROR_PATH) :
	file_put_contents(LOG_FILE, "$file uploaded on ".date("m/d/Y h:i:s")."\r\n",FILE_APPEND);
	//echo $rez;
	download_torrent($file);
}

function download_torrent($file) 
{
	$search_url = SITE_ROOT.'/browse.php?search='.$file.'&searchin=title&incldead=2';
	$ch = curl_init($search_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);

	preg_match('/download\.php\?torrent=([0-9]+)/', $rez, $sub);
	//print_r($sub);	
	$id = $sub[0];
	
	$download_url = SITE_ROOT.'/'.$id;
	$ch = curl_init($download_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);

	file_put_contents(TORRENT_PATH.'/'.$file.".torrent", $rez);
}

function scan_folder()
{
	$dir = UPLOAD_PATH;
	$dir_done = MOVE_PATH;
	
	if ( !is_dir($dir_done) )
	{
		$ok = mkdir($dir_done);
		if (!$ok) die('Cannot create destination folder!');
	}
	
	$dh = opendir($dir);
	while ( $file = readdir($dh) )
	{
		if ($file == '.' || $file == '..') continue;
		$file_full = $dir.'/'.$file;
		if ($file_full == MOVE_PATH) continue;
		$ext = pathinfo($file_full, PATHINFO_EXTENSION);
		make_upload($file_full, $ext, $dir_done);
	}
}

test_login();
scan_folder();
?>
