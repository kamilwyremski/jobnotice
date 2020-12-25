<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2021 by IT Works Better https://itworksbetter.net
 * Project by Kamil Wyremski https://wyremski.pl
 *
 * All right reserved
 *
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANCIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

 function GetUsernameFromFacebookURL(string $url) {
     $correctURLPattern = '/^https?:\/\/(?:www|m)\.facebook.com\/(?:profile\.php\?id=)?([a-zA-Z0-9\.]+)$/';
     if (!preg_match($correctURLPattern, $url, $matches)) {
         return '';
     }
     return $matches[1];
 }

function generateToken(string $name){
	if(empty($_SESSION['token'])){
		$_SESSION['token'] = [];
	}
	if(!empty($_SESSION['token'][$name])){
		$token = $_SESSION['token'][$name];
	}else{
		$token = bin2hex(random_bytes(32));
		$_SESSION['token'][$name] = $token;
	}
	return $token;
}

function checkToken(string $name, string $token = ''){
	if(!$token and isset($_POST['token'])){
		$token = $_POST['token'];
	}
	$check = false;
	if($token and !empty($_SESSION['token'][$name]) and hash_equals($_SESSION['token'][$name], $token)){
		$check = true;
		unset($_SESSION['token'][$name]);
	}
	return $check;
}

function makeFirstLetterSmall(string $in) {
    $x = explode(" ",$in);
    $x[0] = mb_convert_case($x[0],MB_CASE_LOWER,"UTF-8");
    $out = implode(" ",$x);
    return $out;
}

function path(string $controller, int $id = 0, string $slug = ''){
	global $links, $settings;
	if($controller=='classified'){
		return $id.'-'.$slug;
	}elseif(isset($links[$controller])){
		if($id and $slug and $controller!='profile'){
			return $links[$controller].'/'.$id.'-'.$slug;
		}elseif($slug){
			return $links[$controller].'/'.$slug;
		}elseif($id){
			return $links[$controller].'/'.$id;
		}else{
			return $links[$controller];
		}
	}elseif($controller=='rules'){
		return $links['info'].'/2-'.$settings['url_rules'];
	}elseif($controller=='privacy_policy'){
		return $links['info'].'/1-'.$settings['url_privacy_policy'];
	}
}

function absolutePath(string $controller, int $id = 0, string $slug = ''){
	global $settings;
	return $settings['base_url'].'/'.path($controller,$id,$slug);
}

function showCurrency(int $amount){
	global $settings;
	return number_format($amount/100,2,",",".").' '.$settings['currency'];
}

function arrangeAlphabetically(string $table, string $condition = 'true'){
	global $db;
	$position = 1;
	$sth = $db->query('SELECT id FROM `'._DB_PREFIX_.$table.'` WHERE '.$condition.' ORDER BY name');
	foreach($sth as $row){
		$db->query('UPDATE '._DB_PREFIX_.$table.' SET position='.$position.' WHERE id='.$row['id'].' LIMIT 1');
		$position++;
	}
}

function getPosition(string $table, string $condition = 'true'){
	global $db;
	$sth = $db->query('SELECT position FROM `'._DB_PREFIX_.$table.'` WHERE '.$condition.' ORDER BY position DESC LIMIT 1');
	$pos = $sth->fetch(PDO::FETCH_ASSOC);
	if(!empty($pos)){
		return $pos['position']+1;
	}else{
		return 1;
	}
}

function setPosition(string $table, int $id, int $position, string $plusminus, string $additional_condition = 'true'){
	global $db;
	if($table and $id>0 and $position>0){
		if($plusminus=='+'){$condition = '<'; $sort = 'desc';}else{$condition = '>'; $sort = 'asc';}
		$sth = $db->query('SELECT id, position FROM `'._DB_PREFIX_.$table.'` WHERE position '.$condition.' '.$position.' AND '.$additional_condition.' ORDER BY position '.$sort.' LIMIT 1');
		$pos = $sth->fetch(PDO::FETCH_ASSOC);
		if($pos){
			$sth = $db->query('UPDATE `'._DB_PREFIX_.$table.'` SET position='.$pos['position'].' WHERE id='.$id.' LIMIT 1');
			$sth = $db->query('UPDATE `'._DB_PREFIX_.$table.'` SET position='.$position.' WHERE id='.$pos['id'].' LIMIT 1');
		}
	}
}

function orderBy(string $sort=' id DESC '){
	if(!empty($_GET['sort'])){
		$sort = slug($_GET['sort']);
		if(isset($_GET['sort_desc'])){
			$sort .= ' DESC ';
		}
	}
	return $sort;
}

function paginationPageFrom(int $limit){
	$limit_start = 0;
	if(isset($_GET['page']) and is_numeric($_GET['page']) and $_GET['page']>0){
		$limit_start = ($_GET['page']-1)*$limit;
	}
	return $limit_start;
}

function generatePagination(int $limit){
	global $render_variables, $db;
	$limit_start = paginationPageFrom($limit);
	$page_number = 1;
	if(isset($_GET['page']) and is_numeric($_GET['page']) and $_GET['page']>0){
		$page_number = $_GET['page'];
	}

	$sth = $db->query('SELECT FOUND_ROWS()');
	$page_count = ceil($sth->fetch(PDO::FETCH_ASSOC)['FOUND_ROWS()']/$limit);

	if($page_number<6){
		$pagination['page_start'] = 1;
	}else{
		$pagination['page_start'] =  $page_number-4;
	}

	$gets_admin = $gets = $_GET;
	unset($gets['page'],$gets['category_path'],$gets['path'],$gets['slug'],$gets['id']);
	$gets_admin = $gets;
	$page_url['page_admin'] = http_build_query($gets);
	unset($gets_admin['sort'], $gets_admin['sort_desc']);
	$page_url['sort_admin'] = http_build_query($gets_admin);
	$page_url['page'] = http_build_query($gets);
	unset($gets['sort']);
	$page_url['sort'] = $gets;

	$pagination['page_url'] = $page_url;
	$pagination['page_count'] = $page_count;
	$pagination['page_number'] = $page_number;
	$pagination['limit_start'] = $limit_start;

	$render_variables['pagination'] = $pagination;
}

function trans(string $text){
	global $translate;
	if(isset($translate[$text])){
		return ($translate[$text]);
	}else{
		return ($text);
	}
}

function langList(){
	$files = array_diff(scandir(realpath(dirname(__FILE__)).'/../config/langs/'), ['.', '..']);
	foreach($files as $key=>$file){
		$path_parts = pathinfo($file);
		if($path_parts['extension']=='php'){
			$langList[] = $path_parts['filename'];
		}
	}
	return($langList);
}

function langLoad(string $lang='en'){
	global $translate, $links;
	if(!in_array($lang, langList())){$lang = 'en';}
	require_once(realpath(dirname(__FILE__)).'/../config/langs/'.$lang.'.php');
	return $lang;
}

function showInfo(string $info){
	global $render_variables;
	switch ($info) {
		case 'new_account':
			$render_variables['alert_success'][] = trans('The account has been established. To your e mail was sent message with an activation code');
			break;
		case 'classified_activated':
			$render_variables['alert_success'][] = trans('The classified has been correctly activated on the site');
			break;
		case 'classified_saved':
			$render_variables['alert_success'][] = trans('Your classified has been saved');
			break;
		case 'classified_deleted':
			$render_variables['alert_success'][] = trans('Successfully deleted');
			break;
		case 'email_confirmed':
			$render_variables['alert_success'][] = trans('The email address has been successfully confirmed');
			break;
		case 'classifieds_only_employer':
			$render_variables['alert_success'][] = trans('Classifieds can be added only by logged employers');
			break;
	}
}

function checkInfo(){
	if(!empty($_SESSION['flash'])){
		showInfo($_SESSION['flash']);
		unset($_SESSION['flash']);
	}
}

function slug(string $string){
	return strtolower(slugWithUpper($string));
}

function slugWithUpper(string $string){
	$cyr = [
      'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
      'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
      'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
      'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
	];
	$lat = [
		'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
		'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
		'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
		'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
	];
	$string = str_replace($cyr, $lat, $string);
	$string = trim(str_replace([' ','&','%','$',':',',','/','=','?','Ę','Ó','Ą','Ś','Ł','Ż','Ź','Ć','Ń','ę','ó','ą','ś','ł','ż','ź','ć','ń'], ['-','-','-','','','','','','','E','O','A','S','L','Z','Z','C','N','e','o','a','s','l','z','z','c','n'], $string));
	$string = preg_replace("/[^a-zA-Z0-9-_]+/", "", $string);
	$string = trim($string,'-');
	do{
		$string_old = $string;
		$string = str_replace("--", "-", $string);
	}while($string != $string_old);
	return $string;
}

function isSlug(string $string){
	if($string and !preg_match('/[^a-z0-9-_]/', $string)){
		return true;
	}
	return false;
}

function getClientIp() {
	if(!empty($_SERVER['HTTP_CLIENT_IP'])){
		return $_SERVER['HTTP_CLIENT_IP'];
	}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}elseif(!empty($_SERVER['REMOTE_ADDR'])){
		return $_SERVER['REMOTE_ADDR'];
	}else{
		return 'SERVER';
	}
}

function getCoordinates(string $address){
	global $settings;
	if($address){
		$url = "https://maps.google.com/maps/api/geocode/json?address=".urlencode($address)."&key=".urlencode($settings['google_maps_api2'])."&sensor=false";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = json_decode(curl_exec($ch));
		curl_close($ch);
		if($response->results and $response->results[0]){
			return ['lat' => $response->results[0]->geometry->location->lat, 'long' => $response->results[0]->geometry->location->lng];
		}else{
			return ['lat' => 0, 'long' => 0];
		}
	}
	return ['lat' => 0, 'long' => 0];
}

function webAddress(string $address=''){
	if(substr($address, 0, 7) != "http://" and substr($address, 0, 8) != "https://" and $address !='') {
		$address = 'http://'.$address;
	}
	if(substr($address, -1)=='/'){
		$address = substr($address,0,-1);
	}
	return trim($address);
}

function makeAbsoluteUrl(string $address){
	global $settings;
	if(substr($address, 0, 7) != "http://" and substr($address, 0, 8) != "https://" and $address !='') {
		if(substr($address, 0, strlen($settings['base_url'])) != $settings['base_url']) {
			if(substr($address, 0, 1)!='/'){
				$address = '/'.$address;
			}
			$address = $settings['base_url'].$address;
		}
	}
	return $address;
}

function getFullUrl(string $address){
	global $settings;
	if(substr($address, 0, 7) != "http://" and substr($address, 0, 8) != "https://" and $address !='') {
		if(substr($address, 0, 1)!='/'){
			$address = '/'.$address;
		}
		$address = $settings['base_url'].$address;
	}
	return $address;
}

function nofollow(string $html) {
	global $settings;
	$skip = $settings['base_url'];
    return preg_replace_callback(
        "#(<a[^>]+?)>#is", function ($mach) use ($skip) {
            return (
                !($skip && strpos($mach[1], $skip) !== false) &&
                strpos($mach[1], 'rel=') === false
            ) ? $mach[1] . ' rel="nofollow">' : $mach[0];
        },
        $html
    );
}
