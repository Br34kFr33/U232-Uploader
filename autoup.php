<?php
define('ROOT_PATH', '/home/upload');
define('UPLOAD_PATH', ROOT_PATH.'/scan');
define('MOVE_PATH', ROOT_PATH.'/move');
define('TORRENT_PATH', ROOT_PATH.'/torrent');

define('SITE_ROOT', 'http://testing.site');
define('ANNOUNCE_URL', 'http://testing.site/announce.php?torrent_pass=00000001a2d74d8af3afcfa33d941fb2');
define('Q_LOGIN', 'http://testing.site/pagelogin.php?qlogin=39c961aae388fa1c8306f4235bb370c670ef367c8878c6efd9931e9b7a88f7ae99afc9d90a87ea1b31be15ddf7fbe821');

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
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	$rez = curl_exec($ch);
	if ($rez) die('Cannot login!');
}

function make_torrent($file)
{
	$info = pathinfo($file);
	$output = TORRENT_PATH.'/'.$info['basename'].'.torrent';
	if (file_exists($output)) unlink($output);
	$cmd = "mktorrent '$file' -o '$output' -a ".ANNOUNCE_URL;
	exec($cmd);
	if (file_exists($output)) return $output;
	else die('Cannot make torrent!');
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

	$source = glob($nfo_file.'/*');	
 	foreach($source as $a) 
	{
	$filename = $a;
	}
	$nfo = file_get_contents($a);
	$match = array("/[^a-zA-Z0-9-+.,&=??????:;*'\"???\/\@\[\]\(\)\s]/","/((\x0D\x0A\s*){3,}|(\x0A\s*){3,}|(\x0D\s*){3,})/","/\x0D\x0A|\x0A|\x0D/");
	$replace = array( "","\n\n","\n");
	$nfo = preg_replace($match, $replace, trim($nfo));

	$imdb = "";
    	if (preg_match('/http:\/\/www.imdb.com\/title\/tt[\d]+\//', $nfo, $matches)) 
	{
        $imdb = $matches[0];
	}
	
	if (preg_match('/hdtv|sdtv/i', $file)) {
	  $cat = 5;
	}elseif (preg_match('/xvid|brrip|bdrip|dvdrip|hdrip/i', $file)) {
	  $cat = 10;
	}else{
	  $cat = 9;
	}
	
	$torrent_info = Array();
	$torrent_info['name'] = $file;
	$torrent_info['descr'] = $nfo;
	$torrent_info['url'] = $imdb;
	$torrent_info['type'] = $cat;
	upload_torrent($torrent, $torrent_info, $file);
}

function test_login()
{
	$login_url = SITE_ROOT.'/mytorrents.php';
	$ch = curl_init($login_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	$rez = curl_exec($ch);

	$torrent_info['MAX_FILE_SIZE']=3145728;
	$torrent_info['poster']='';	
	$torrent_info['youtube']='';	
	$torrent_info['file'] = new CURLFile (TORRENT_PATH.'/'.$file . ".torrent");	
	$torrent_info['description']='Br0kens Uploader Bot';	
	$torrent_info['fontfont']='0';
	$torrent_info['fontsize']='0';
	$torrent_info['request']='0';
	$torrent_info['release_group']='none';
	$torrent_info['strip']=	'strip';

	print_r($torrent_info);
	$upload_url = SITE_ROOT.'/takeupload.php';
	$ch = curl_init($upload_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
	
	if (strpos($rez,'Upload failed!')) echo "$torrent failed \n";
	else echo "$torrent uploaded \n";
}

//scan folder for files
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
