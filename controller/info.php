<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2023 by IT Works Better https://itworksbetter.net
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

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(isset($_GET['id']) and $_GET['id']>0 and !empty($_GET['slug'])){

	$info_page = info::show($_GET['id']);
	if($info_page!=''){
		if($_GET['slug']!=$info_page['slug']){
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".absolutePath('info', $info_page['id'], $info_page['slug']));
			die('redirect');
		}else{
			$render_variables['info_page'] = $info_page;
			$settings['seo_title'] = $info_page['name'].' - '.$settings['title'];
			if($info_page['description']){
				$settings['seo_description'] = $info_page['description'];
			}else{
				$settings['seo_description'] = $info_page['name'].' - '.$settings['description'];
			}
			if($info_page['keywords']){
				$settings['seo_keywords'] = $info_page['keywords'];
			}
		}
	}else{
		throw new noFoundException();
	}
}else{

	if(!empty($_GET['slug'])){
		throw new noFoundException();
	}
	
	$render_variables['info'] = info::list();
	$settings['seo_title'] = trans('Info').' - '.$settings['title'];
	$settings['seo_description'] = trans('Info').' - '.$settings['description'];
}
