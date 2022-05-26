<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
<title>Crawler</title>
<div style='padding: 20px;'>
	<form  action='index_textarea.php' spellcheck="false">
		<p><b><a href='<?php echo $_SERVER['PHP_SELF']; ?>' style=' text-decoration: none; color: black;'>Upload ASINs:</a></b></p>
		<p><textarea rows="5" cols="45" name="urls"><?php echo @$_GET['urls']; ?></textarea></p>
		<p><input type="submit" value="Start"></p>
	</form>
 <?php
 
if ( !isset($_GET['urls']) ) die;

set_time_limit(0);
include_once('simple_html_dom.php');
ini_set("memory_limit","1024M");

$csv_content = '"asin","letter","name","image"'.PHP_EOL ;
$csv_file_path = 'products.csv';

$urls_file_path = 'index.php';

if ( ! is_writable(dirname($urls_file_path))) {
	echo '<h3 style="color: red">Directory '.realpath( dirname($urls_file_path) ). ' must be writable!<br>
	"chmod o+w '.realpath( dirname($urls_file_path) ). '" in cmd should help!
	<h3>';
}

$urls = trim($_GET['urls']);

//$urls = file_get_contents($_FILES['urls']['tmp_name']);

$urls = preg_split("/\r\n|\n|\r/", $urls); //split by new line
unset($array[0]);

$i = 0;

foreach( $urls as $url ) {
	$i = $i +1;
	echo	'<h5>'.$i.'. '.$url.'</h5><br>';
	check_link(trim($url));
}

file_put_contents($csv_file_path, $csv_content);

echo '<br><br>Data stored in <a href="'.$csv_file_path.'">'.$csv_file_path.'</a>';

function check_link($asin) {

	global $csv_content;
	$url = 'https://www.amazon.com/gp/product/' . $asin;

//	echo $url.'<br>';
//	$pure_html = file_get_contents('test.html');

	sleep(1);
	$pure_html = curl($url);

	$html = str_get_html( $pure_html );
	$product_name = html_entity_decode( trim($html->find('#productTitle', 0)->innertext), ENT_QUOTES, 'UTF-8' );

//	echo '<br><a href="'.$url.'" style="color: green;">'.$product_name.'</a><br><br>';

	preg_match('/jQuery.parseJSON(.*)/', $pure_html , $matches, PREG_OFFSET_CAPTURE);
	$amz_json = $matches[1][0];
//echo $amz_json;die;
	$amz_json = substr($amz_json, 2, -3);

//echo $amz_json;die;

	$amz_json = json_decode(stripslashes($amz_json));
	$color = $amz_json->landingAsinColor;

	$imgs = $amz_json->colorImages->$color;
	$i = 0;
	$alphabet = range('a', 'z');
	foreach ($imgs as $img){
		$letter = $alphabet[$i];
		echo '<img style="border:1px solid green; margin: 3px;" height=200 src='.$img->hiRes.'>';
		$csv_content = $csv_content.'"'.$asin.'","'.$letter.'","'.$product_name.'","'.$img->hiRes.'"'.PHP_EOL;		
		$i = $i +1;
	}

}

function curl( $url, $retry = 3 ){
	$user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36';

	if( $retry > 5 ) {
		print "Maximum 5 retries are done, skipping!\n";
		return "in loop!";
	}
	
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt ($ch, CURLOPT_HEADER, TRUE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
//	curl_setopt ($ch, CURLOPT_REFERER, 'http://www.google.com.ua/');
//curl_setopt($ch, CURLOPT_PROXY, 'socks5://144.76.64.245:9100');

	curl_setopt($ch,CURLOPT_ENCODING , "");
	curl_setopt ($ch, CURLOPT_COOKIEFILE,"./cookie.txt");
	curl_setopt ($ch, CURLOPT_COOKIEJAR,"./cookie.txt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);

	// handling the follow redirect
	if(preg_match("|Location: (https?://\S+)|", $result, $m)){
		echo "Manually doing follow redirect! -> $m[1] <br>";
		return curl($m[1], $user_agent, $retry + 1);
	}

	// add another condition here if the location is like Location: /home/products/index.php

	return $result;
}

?>