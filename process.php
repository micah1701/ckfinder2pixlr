<?php
/*
* CKFinder 2 pixlr
* ========
* http://code.google.com/p/ckfinder2pixlr
* Copyright (C) 2010 - 2012  Micah J. Murray
*
*
* CKFinder extension: Integrate the pixlr image editing application utilizing the pixlr.com API
*/

//grab the Framework Session ID from the cookie.  You can comment out this line if not using framework (such as Kohanna or CakePHP)
#session_id($_COOKIE['session']);  
session_start();

//turn on errors for testing.  Otherwise, comment out these two lines.
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

if($_GET['token'] != $_SESSION['pixlr']['token']){ // value passed in orig URL as part of the return target
 exit("ERROR: You are not authorized to edit images at this time");
}

$querystring = $_SERVER['REQUEST_URI']; // something like: /folder/token_value?image=http://www.pixlr/_temp/filename.jpg&type=jpg&title=Your_picture
$stringParts = explode("?",$querystring);  
$origQueryString = $stringParts[1]; //drops everything before the question mark.  This is what the request_url would look like if not for the token hack.

$origQueryParts = explode("&",$origQueryString);  //break the query string into parts.
foreach($origQueryParts as $qsPart){ 
  $part_parts = explode("=",$qsPart);  //then break each part into is paramater => value
  $_GET[$part_parts[0]] = $part_parts[1];
}

//make sure values sent to this script aren't malicious.
function get($var){
 return htmlspecialchars($_GET[$var]);
}

$image = get('image'); //URL to image at pixlr
$type  = get('type');  //`jpg` or `png`
$state = get('state'); // state of the image:  `new` or `fetced`
$title = get('title'); // user's new image title.

//if fopen() can't read the file off the pixlr site do to config option (namely, allow_url_fopen == off)
//then use cUrl to grab the image off of pixlr and save it to a temporary file before saving it
if(!ini_get('allow_url_fopen')){
 $tmp_file_name = "temp_img.".$type;  // make sure the files "tmp_file.jpg" and "tmp_file.png" are chmod 777
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,$image);
 $fp = fopen($tmp_file_name,'w+');
 curl_setopt($ch, CURLOPT_FILE, $fp);
 curl_exec ($ch);
 curl_close ($ch);
 fclose($fp);
 $image = $tmp_file_name;
}

$filename = $title.'.'.$type;
$saveLocation = $_SESSION['pixlr']['ImagePath']; //use the original filepath - even if user entered a new name.

if($type == "jpg"){
 $srcImage = imagecreatefromjpeg($image);
 if(!imagejpeg($srcImage, $saveLocation)){
	 exit("ERROR: Image not saved");
 }

}elseif($type == "png"){
 $srcImage = imagecreatefrompng($image);
 imagepng($srcImage, $saveLocation);
}

// create "thumbnail" version of edited image to replace exisiting thumb
// this is a bit of a hack, as I'm using my own script for re-sizing thumbs, not the thumb creating tool of CKFinder

  list($width, $height) = getimagesize($saveLocation);
    if($width > $height){
	 $ratio = $height / $width;
	 $width = 100;
	 $height = 100 * $ratio;
	}else{
	 $ratio = $width / $height;
	 $width = 100 * $ratio;
	 $height = 100;
	}	 

  //figure out where the thumbnails are located
  $thumbLocation = $_SESSION['pixlr']['thumbLocation'];

 if($type == "jpg"){
  $srcImage = imagecreatefromjpeg($image);
  $newImage = imagecreatetruecolor($width, $height);
  imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $width, $height, imagesx($srcImage), imagesy($srcImage));
  imagejpeg($newImage, $thumbLocation);

 }elseif($type == "png"){
  $srcImage = imagecreatefrompng($image);
  $newImage = imagecreatetruecolor($width, $height);
  imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $width, $height, imagesx($srcImage), imagesy($srcImage));
  imagepng($newImage, $thumbLocation);    
 }

unlink($fp);

header("location: ".$_SESSION['pixlr']['return']);
?>