<?php

require_once('oauth.class.php');
require_once('process.php');

// Some code from https://gist.github.com/sebietter/4749689/

$saveDir = "i/"; // place where the images should be saved.
$domain = "https://twitter.domain.com"; // your domain.

//server-side directory
$directory_self = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
$uploadsDirectory = $_SERVER['DOCUMENT_ROOT'] . $directory_self . $saveDir;

$valid_users = array('your_twitter_handle_here');

$oauthecho = new TwitterOAuthEcho();
$oauthecho->userAgent = 'My Custom Image Service App 1.0';
$oauthecho->setCredentialsFromRequestHeaders();

if ($oauthecho->verify()) {
	$userInfo = json_decode($oauthecho->responseText, true);
	if(in_array($userInfo['screen_name'], $valid_users)) {
		// Image filetype check source:
		// http://designshack.net/articles/php-articles/smart-file-type-detection-using-php/
		$tempFile = $_FILES['media']['tmp_name'];
		//$imginfo_array = getimagesize($tempFile);
		$file_info = filesize($tempFile);

		//if ($imginfo_array !== false) {
		if($file_info > 0)
		    $mime_type = $imginfo_array['mime'];
		    $mime_array = array("video/quicktime", "image/png", "image/jpeg", "image/gif", "image/bmp", "video/mp4");
		    if(in_array($_FILES['media']['type'], $mime_array)) {
		    //if (in_array($mime_type , $mime_array)) {

		  	//generate random filename
				while(file_exists($uploadFilename = $uploadsDirectory.time().'-'.$_FILES['media']['name'])){$now++;}
				
				//upload the file to the webserver
				move_uploaded_file($_FILES['media']['tmp_name'], $uploadFilename);

				//generate the filename that will be given to Tweetbot
				if(isset($_GET['gif']) && $_GET['gif'] != 0) {
					$outputFilename = $domain . '/' . $saveDir . 'gif/' . basename($uploadFilename) . '.gif';
					$ext = 'gif';
				} else {
					$outputFilename = $domain . '/' . $saveDir  . basename($uploadFilename);
					$ext = pathinfo($uploadFilename);
					$ext = $ext['extension'];
				}

				if(isset($_GET['url']) && $_GET['url'] != 0) {
					$outputFilename = file_get_contents('http://jbuk.ly/-/?api=b0d3ac71c959e429e8e29e58d5c330af&url=' . urlencode($outputFilename));
					$outputFilename .= '.' . $ext;
				}
				$highres = (isset($_GET['highres']) && $_GET['highres'] != 0);
				$watermark_text = (isset($_GET['watermark']) && $_GET['watermark'] != 0) ? '@' . $userInfo['screen_name'] : ' ';

				watermark_image($uploadsDirectory, basename($uploadFilename), $watermark_text, $highres);
				file_put_contents('log.txt', $outputFilename);

				//respond with JSON encoded media URL
				$response = array(url=>$outputFilename);
				echo json_encode($response);
		    //}
		} else {
		    echo "This is not a valid image file";
		}
	} else {
		echo "Not a valid user for this service.";
	}
} else {
	echo "Not a valid user according to Twitter.";
}



?>
