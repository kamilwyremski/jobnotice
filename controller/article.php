<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 by IT Works Better https://itworksbetter.net
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

if($settings['enable_articles'] and isset($_GET['id']) and $_GET['id']>0 and !empty($_GET['slug'])){

  $article = article::show($_GET['id']);
	if($article){
		if($_GET['slug']!=$article['slug']){
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".absolutePath('article', $article['id'], $article['slug']));
			die('redirect');
		}else{
			$render_variables['article'] = $article;
			$settings['seo_title'] = $article['name'].' - '.$settings['title'];
			if($article['description']){
				$settings['seo_description'] = $article['description'];
			}else{
				$settings['seo_description'] = $article['name'].' - '.$settings['description'];
			}
			if($article['keywords']){
				$settings['seo_keywords'] = $article['keywords'];
			}
			if($article['thumb']){$settings['logo_facebook'] = $article['thumb'];}
		}
	}else{
		throw new noFoundException();
	}

}else{
	throw new noFoundException();
}
