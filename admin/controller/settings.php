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
		if($_POST['action']=='save_settings' and !empty($_POST['base_url']) and !empty($_POST['email']) and !empty($_POST['title']) and checkToken('admin_save_settings')){

			$_POST['base_url'] = webAddress($_POST['base_url']);

			settings::saveArrays(
				['base_url','email','title','keywords','description','analytics','recaptcha_site_key','recaptcha_secret_key','add_classifieds','number_char_title','google_maps_api','google_maps_api2','google_maps_lat','google_maps_long','google_maps_zoom_add','google_maps_zoom_classified','google_maps_long','limit_page','limit_page_index','limit_similar','photo_max','photo_max_size','photo_max_height','photo_max_width','photo_quality','attachment_max_size','smtp_host','smtp_mail','smtp_user','smtp_password','smtp_port','smtp_secure'],
				['automatically_activate_classifieds','enable_articles','rss','generate_sitemap','check_ip_user','required_type','required_category','required_subcategory','required_phone','required_address','required_state','required_salary','google_maps','show_similar_classifieds','photo_add','watermark_add','hide_data_not_logged','hide_phone','hide_email','hide_views','mail_attachment','smtp']
			);
			if($settings['lang']!=$_POST['lang']){
				unset($translate);
				$_POST['lang'] = langLoad($_POST['lang']);
				settings::save('lang');
			}
			getSettings();
			$render_variables['alert_success'][] = trans('Changes have been saved');

		}elseif($_POST['action']=='send_test_message' and !empty($_POST['email']) and !empty($_POST['subject']) and !empty($_POST['message']) and checkToken('admin_send_test_message')){
			if(mail::send('test',$_POST['email'],['subject'=>$_POST['subject'], 'message'=>$_POST['message']])){
				$render_variables['alert_success'][] = trans('The message was correctly sent');
			}else{
				$render_variables['alert_danger'][] = trans('The message was not sent');
			}
		}
	}

	$render_variables['lang_list'] = langList();

	$title = trans('Settings').' - '.$title_default;

}
