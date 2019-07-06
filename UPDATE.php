<?php
echo "Ile: ";
$multipler = (integer)fgets(STDIN);
$multipler = $multipler < 1 ? 1 : $multipler;
echo "FTP: ";
$send = fgets(STDIN);
if(strpos($send, 't') !== false || strpos($send, 'y') !== false)
	$send = true;
else
	$send = false;
$data = file_get_contents("UPDATE.json");
$data = json_decode($data, true);
$old = (double)$data['version'];
$ver = $old+(0.01*$multipler);
$modify = $data['modify'];
echo "Wersja: $old->$ver ".PHP_EOL;
function getFiles($directory){
	global $modify;
    $files = array();
    foreach(scandir($directory) as $entry){
        if ($entry == '.' || $entry == '..' || $entry == 'UPDATE.json' || $entry == 'UPDATE.bat' || $entry == 'UPDATE.php' || $entry == 'logs.log' || $entry == 'bot.bat' || $entry == 'cache' || $entry == 'config' || strpos($entry, ".zip") !== false) continue;
		$path = "$directory\\$entry";
        if (is_dir($path)) {
            $files = array_merge($files, getFiles($path));
        }else if(filemtime($path) > $modify){
			$files[] = $path;
		}
    }
    return $files;
}
$modified = getFiles(getcwd());
if(empty($modified)){
	die("Brak zmodyfikowanych plikow.".PHP_EOL);
}

$zip = new ZipArchive();
if(($opened = $zip->open("update-$ver.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) !== true){
	die("Wystapil blad podczas tworzenia archiwum.".PHP_EOL);
}
foreach($modified as $path){
	$_path = str_replace(getcwd().'\\', '', $path);
	$zip->addFile($path, $_path);
	
}
$zip->close();
echo "Spakowano ".count($modified)." plikow do archiwum.".PHP_EOL;
if($send){
	if(!($ftp = @ftp_connect("hostname")))
		die("Wystapil blad podczas laczenia sie z ftp.".PHP_EOL);
	else if(!($login = @ftp_login($ftp, "user", "password")))
		die("Wystapil blad podczas logowania sie na ftp.".PHP_EOL);
	ftp_pasv($ftp, true);
	if(!ftp_put($ftp, "bot/update-$ver.zip", "update-$ver.zip", FTP_BINARY))
		die("Wystapil blad podczas wysylania na ftp.".PHP_EOL);
	ftp_close($ftp);
	unlink("update-$ver.zip");
	echo "Plik archiwum zostal wyslany na ftp.".PHP_EOL;
}
file_put_contents("UPDATE.json", json_encode(array('version' => $ver, 'modify' => time())));
file_put_contents("cache/version.txt", $ver);
?>
