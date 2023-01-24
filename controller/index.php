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

$render_variables['slider'] = slider::list();

$render_variables['classifieds'] = classified::list($settings['limit_page_index'],[],'index_page');

if($settings['index_box_subcategories']){
	$render_variables['categories'] = category::listWithSubcategories();
}elseif($settings['search_box_category']){
	$render_variables['categories'] = category::list();
}

if($settings['search_box_state']){
	$render_variables['states'] = state::listAll();
}
if($settings['search_box_type']){
	$render_variables['types'] = type::list();
}

if($settings['enable_articles']){
	$render_variables['articles'] = article::list(4);
}
