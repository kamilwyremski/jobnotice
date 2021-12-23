<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2022 by IT Works Better https://itworksbetter.net
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

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='add_article' and !empty($_POST['name']) and checkToken('admin_add_article')){
      $id = article::add($_POST);
			header('Location: ?controller=article&id='.$id);
			die('redirect');
		}elseif($_POST['action']=='edit_article' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_article')){
			article::edit($_POST['id'],$_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}
	}

	$title = trans('Article').' - '.$title_default;

	if(isset($_GET['id']) and $_GET['id']>0){
		$article = article::show($_GET['id']);
		if($article!=''){
			$title = $article['name'].' - '.$title;
			$render_variables['article'] = $article;
		}
	}

}
