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

class newsletter {

	public static function check(){
		global $db;
		$newsletter_error = '';
		$newsletter_input = [];
		$newsletter_info = '';

		if(isset($_POST['action']) and $_POST['action']=='newsletter_add' and !empty($_POST['email']) and isset($_POST['rules']) and checkToken('newsletter_add')){
			
			if(!settings::checkCaptcha($_POST)){
				$newsletter_error = trans('Invalid captcha code. Show thay you are not robot!');
			}elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$newsletter_error = trans('Incorrect e-mail address.');
			}else{
				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'newsletter WHERE email=:email LIMIT 1');
				$sth->bindValue(':email', $_POST['email'], PDO::PARAM_STR);
				$sth->execute();
				if($sth->fetchColumn()){
					$newsletter_error = trans('E-mail already exists in the database.');
				}
			}

			if($newsletter_error){
				$newsletter_input = $_POST;
			}else{
				static::add($_POST['email']);
				$newsletter_info = trans('Check your email inbox to confirm the subscription to the newsletter');
			}
		}

		if(!empty($_GET['newsletter_activation_code'])){
			if(static::checkCode($_GET['newsletter_activation_code'])){
				$newsletter_info = trans('The email address has been successfully confirmed');
			}else{
				$newsletter_error = trans('Incorrect activation code or email has already been confirmed');
			}
		}elseif(!empty($_GET['newsletter_cancel'])){
			if(static::checkCancel($_GET['newsletter_cancel'])){
				$newsletter_info = trans('The email address was successfully deleted');
			}else{
				$newsletter_error = trans('The email address not exists in newsletter base');
			}
		}
		return ['newsletter_info'=>$newsletter_info,'newsletter_input'=>$newsletter_input,'newsletter_error'=>$newsletter_error];
	}

	public static function add(string $email){
		global $db;

		$code = bin2hex(random_bytes(32));

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'newsletter`(`email`, `code`, `ip`) VALUES (:email,:code,:ip)');
		$sth->bindValue(':email', strip_tags($email), PDO::PARAM_STR);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();

		mail::send('newsletter_add',$email,['email'=>$email, 'newsletter_activation_code'=>$code]);
	}

	public static function checkCode(string $code){
		global $db;

		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'newsletter WHERE code=:code AND active="0" LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		$newsletter = $sth->fetch(PDO::FETCH_ASSOC);

		if($newsletter){
			static::activate($newsletter['id']);
			return true;
		}else{
			return false;
		}
	}

	public static function activate(int $id){
		global $db;
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'newsletter SET active="1" WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function checkCancel(string $code){
		global $db;

		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'newsletter WHERE code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		$newsletter = $sth->fetch(PDO::FETCH_ASSOC);

		if($newsletter){
			static::remove($newsletter['id']);
			return true;
		}else{
			return false;
		}
	}

	public static function list(int $limit=100){
		global $db;
		$newsletter = [];
		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM '._DB_PREFIX_.'newsletter ORDER BY '.orderBy().' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$newsletter[] = $row;
		}
		generatePagination($limit);
		return $newsletter;
	}

	public static function remove(int $id){
		global $db;
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'newsletter WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}
}
