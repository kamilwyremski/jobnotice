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

if($admin->is_logged()){

	if(isset($_POST['select_1']) and isset($_POST['select_2']) and !empty($_POST['date_from']) and !empty($_POST['date_to'])){

		function plot_select($select){
			global $db;
			$result = [];
			$begin = new DateTime($_POST['date_from']);
			$end   = new DateTime($_POST['date_to']);
			for($i = $begin; $i <= $end; $i->modify('+1 day')){
				$data[0] = $i->format("Y-m-d");
				$data[1] = 0;
				$result[] = $data;
			}
			switch($select){
				case 'logins':
					$sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'logs_user WHERE date>=:date_from and date<=:date_to GROUP BY date(date)');
					break;
				case 'unique_logins':
					$sth = $db->prepare('SELECT date, count(1) as number from (select id, date from '._DB_PREFIX_.'logs_user where date>=:date_from and date<=:date_to group by date(date), user_id) t1 group by date(date)');
					break;
				case 'registration':
					$sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'user WHERE date>=:date_from AND date<=:date_to GROUP BY date(date)');
					break;
				case 'activation_users':
					$sth = $db->prepare('SELECT activation_date as date, count(1) as number FROM '._DB_PREFIX_.'user WHERE activation_date>=:date_from AND activation_date<=:date_to GROUP BY date(activation_date)');
					break;
				case 'classifieds':
					$sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'classified WHERE date>=:date_from and date<=:date_to GROUP BY date(date)');
					break;
				case 'views_classifieds':
					$sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'logs_classified WHERE date>=:date_from AND date<=:date_to GROUP BY date(date)');
					break;
				case 'newsletter':
					$sth = $db->prepare('SELECT date, count(1) as number FROM '._DB_PREFIX_.'newsletter WHERE active=1 AND date>=:date_from AND date<=:date_to GROUP BY date(date)');
					break;
				default:
					return [];
			}
			$sth->bindValue(':date_from', $_POST['date_from'], PDO::PARAM_STR);
			$sth->bindValue(':date_to', $_POST['date_to']." 23:59", PDO::PARAM_STR);
			$sth->execute();
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
				foreach($result as $key=>$value){
					if($value[0] == date("Y-m-d",strtotime($row['date']))){
						$result[$key][1] = (int) $row['number'];
					}
				}
			}
			return $result;
		}

		$statistics = [];
		$statistics[] = plot_select($_POST['select_1']);
		$statistics[] = plot_select($_POST['select_2']);
		echo json_encode($statistics);
		die();
	}

	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'user');
	$statistics['users'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'user WHERE register_fb=1');
	$statistics['users_register_fb'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'user WHERE register_google=1');
	$statistics['users_register_google'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'classified');
	$statistics['classifieds'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'classified WHERE active=1');
	$statistics['classifieds_active'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_mail');
	$statistics['logs_mails'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_classified');
	$statistics['logs_classifieds'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_user');
	$statistics['logs_users'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'photo');
	$statistics['photos'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'category');
	$statistics['categories'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'reset_password');
	$statistics['reset_password'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'mails_queue');
	$statistics['mails_queue'] = $sth->fetchColumn();
	$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'newsletter WHERE active=1');
	$statistics['newsletter'] = $sth->fetchColumn();

	$render_variables['statistics'] = $statistics;

	$title = trans('Statistics').' - '.$title_default;
}
