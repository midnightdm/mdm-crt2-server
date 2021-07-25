<?php 
/* * * * * 
 * classes/crtfunctions_helper.php
 * 
 * File copied 4/19/21 from ../application/helpers/crtfunctions_helper.php
 * 
 */

function is_selected($title, $test) {
  if($title===$test) {
    return "selected";
  } else {
    return "";
  }
}

function base_url() {
  return "localhost/mdm-crt/";
}

function getTimeOffset() {
  $tz = new DateTimeZone("America/Chicago");
  $dt = new DateTime();
  $dt->setTimeZone($tz);
  return $dt->format("I") ? -18000 : -21600;
}

function getNow($dateString="Y-m-d H:i:s") {  
  return date($dateString, (time()+getTimeOffset()));
}

function getYesterdayRange() {
  $offset = -0;
  $today = getdate();
  $todayMidnight = mktime(0,0,0,$today['mon'],$today['mday'])+$offset;
  $yesterdayMidnight = $todayMidnight - 86400 +$offset;
  return [$yesterdayMidnight, ($todayMidnight-1)];
}

function getTodayRange() {
  $offset = getTimeOffset(); //-18000;
  $today = getdate();
  $todayMidnight = mktime(0,0,0,$today['mon'], $today['mday']);
  return [$todayMidnight, $today[0]];
}

function getLast24HoursRange() {
  $offset = getTimeOffset(); //-18000;
  $today = getdate();
  return [($today[0]-86400), $today[0]];
}

function getLast7DaysRange() {
  $offset = getTimeOffset(); //-18000;
  $today = getdate();
  return [($today[0]-604800), $today[0]];
}

function printRange($dateArr) {
  if(!is_array($dateArr)) {
    return "Invalid range array used in printRange()";
  }
  return "Range is ".date('g:ia l, M j', $dateArr[0])." to ".date('g:ia l, M j', $dateArr[1]);
}

//Has server specific 'hard-set' file path
function saveImage($mmsi) {
  $url = 'https://www.myshiptracking.com/requests/getimage-normal/';
  $imgData = grab_page($url.$mmsi.'.jpg');
  //$imgData = grab_image($url.$mmsi.'.jpg');

  $awsKey      = getEnv('AWS_ACCESS_KEY_ID');
  $awsSecret   = getEnv('AWS_SECRET_ACCES_KEY');
  $credentials = new Aws\Credentials\Credentials($awsKey, $awsSecret);

  $s3 = new Aws\S3\S3Client([
      'version'     => 'latest',
      'region'      => 'us-east-2',
      'credentials' => $credentials
  ]);    

  $bucket = getEnv('S3_BUCKET');
  $fileName = 'vessels/mmsi'.$mmsi.'.jpg';
  $s3->upload($bucket, $fileName, $imgData);
  return true;
}

//function to grab page using cURL
function grab_page($url, $query='') {
  //echo "Function grab_page() \$url=$url, \$query=$query\n";
  $ch = curl_init();
  //UA last updated 4/10/21
  $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36";
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_USERAGENT, $ua);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_TIMEOUT, 40);
  curl_setopt($ch, CURLOPT_URL, $url.$query);
  //ob_start();
  return curl_exec($ch);
  //ob_end_clean();
  curl_close($ch);
} 

function grab_image($url){
	$ch = curl_init ();
  $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0';
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, $ua);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
  curl_setopt($ch, CURLOPT_URL, $url);
	return curl_exec($ch); 
}