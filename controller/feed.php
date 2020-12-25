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

if(!$settings['rss']){
	die(trans('RSS feed was switched off'));
}

if(!empty($_GET['slug'])){
	throw new noFoundException();
}

header("Content-Type: application/xml; charset=utf-8");

$classifieds = classified::list(20,$_GET,'index_page');

$rssfeed = '<?xml version="1.0" encoding="utf-8"?>';
$rssfeed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
$rssfeed .= '<channel>';
$rssfeed .= '<title>'.$settings['title'].'</title>';
$rssfeed .= '<link>'.$settings['base_url'].'</link>';
if($settings['logo']!=''){
	$rssfeed .= ' <image>
		<title>'.$settings['title'].'</title>
		<url>'.$settings['logo'].'</url>
		<link>'.$settings['base_url'].'</link>
	</image>';
}
$rssfeed .= '<description>'.$settings['description'].'</description>';
$rssfeed .= '<language>'.$settings['lang'].'</language>';
$rssfeed .= '<lastBuildDate>'.date("D, d M Y H:i:s O").'</lastBuildDate>';
$rssfeed .= '<atom:link href="'.$settings['base_url'].'/php/rss.php" rel="self" type="application/rss+xml" />';
if(!empty($classifieds)){
	foreach($classifieds as $key=>$value){
		$rssfeed .= '<item>';
		$rssfeed .= '<title>'.str_replace('&','&amp;',$value['name']).'</title>';
		$rssfeed .= '<link>'.absolutePath('classified',$value['id'],$value['slug']).'</link>';
		$rssfeed .= '<guid>'.absolutePath('classified',$value['id'],$value['slug']).'</guid>';
		$rssfeed .= '<pubDate>'.date("D, d M Y H:i:s O", strtotime($value['date'])).'</pubDate>';
		$rssfeed .= '<description>';
		$rssfeed .= htmlspecialchars(substr(strip_tags($value['description']),0,400), ENT_XML1, 'UTF-8').'...';
		if($value['thumb']){
			$rssfeed .= '&lt;br&gt;&lt;br&gt;&lt;a href="'.absolutePath('classified',$value['id'],$value['slug']).'"&gt;&lt;img src="'.$settings['base_url'].'/upload/photos/'.$value['thumb'].'" height="80"/&gt;&lt;/&gt;';
		}
		$rssfeed .= '</description>';
		$rssfeed .= '</item>';
	}
}
$rssfeed .= '</channel>';
$rssfeed .= '</rss>';

echo $rssfeed;

die();
