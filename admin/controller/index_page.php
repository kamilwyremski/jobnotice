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
		if($_POST['action']=='save_index_page' and isset($_POST['index_page']) and checkToken('admin_save_index_page')){
		  settings::save('index_page');
			$render_variables['alert_success'][] = trans('Changes have been saved');
			getSettings();
		}elseif($_POST['action']=='add_slide' and checkToken('admin_add_slide')){
      slider::add();
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='save_slides' and checkToken('admin_save_slides')){
      slider::save($_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}
	}

  $render_variables['slider'] = slider::list();

	$title = trans('Index page').' - '.$title_default;

}
