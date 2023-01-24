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

class clipboard {

	public static function add(int $classified_id){
		global $db, $user;
		if($user->getId()){
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'clipboard WHERE user_id=:user_id and classified_id=:classified_id LIMIT 1');
			$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
			$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
			$sth->execute();
			if(!$sth->fetchColumn()){
				$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'clipboard`(`user_id`, `classified_id`) VALUES (:user_id,:classified_id)');
				$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
				$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
				$sth->execute();
			}
		}
	}

	public static function remove(int $classified_id){
		global $db, $user;
		$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'clipboard` WHERE user_id=:user_id AND classified_id=:classified_id');
		$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
	}
}
