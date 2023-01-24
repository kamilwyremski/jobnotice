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

class document {

	public static $extensions = ['pdf','doc','docx','txt','xls','csv','xlsx'];
	
	public static function add(int $user_id, string $name){
		global $db;

		$path_parts = pathinfo($_FILES['file']['name']);
		$path_parts['extension'] = strtolower($path_parts['extension']);

		if(in_array($path_parts['extension'], static::$extensions)){

			chmod(_FOLDER_DOCUMENTS_, 0777);

			do{
				$url = settings::randomPassword(64);
			}while(file_exists(_FOLDER_DOCUMENTS_.$url));

			move_uploaded_file($_FILES['file']['tmp_name'], _FOLDER_DOCUMENTS_.$url);

			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'document`(`user_id`, `name`, `url`, `filename`) VALUES (:user_id,:name,:url,:filename)');
			$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$sth->bindValue(':name', $name, PDO::PARAM_STR);
			$sth->bindValue(':url', $url, PDO::PARAM_STR);
			$sth->bindValue(':filename', $_FILES['file']['name'], PDO::PARAM_STR);
			$sth->execute();
			return true;
		}
		return false;
	}

	public static function list(int $user_id){
		global $db;
		$documents = [];
		if($user_id){
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'document WHERE user_id=:user_id ORDER BY name');
			$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$sth->execute();
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
				$documents[] = $row;
			}
		}
		return $documents;
	}

	public static function checkPermissions(int $id){
		global $user, $db;
		if($user->getId()){
			if($user->moderator){
				return true;
			}else{
				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'document WHERE id=:id AND user_id=:user_id LIMIT 1');
				$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
				$sth->bindValue(':id', $id, PDO::PARAM_INT);
				$sth->execute();
				if($sth->fetchColumn()){
					return true;
				}
			}
		}
		return false;
	}

	public static function show(int $id){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'document WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public static function removeOfUser(int $user_id){
		$documents = static::list($user_id);
		foreach($documents as $document){
			static::remove($document['id']);
		}
	}

	public static function remove(int $id){
		global $db;
		$document = static::show($id);
		if($document){
			unlink(_FOLDER_DOCUMENTS_.$document['url']);
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'document WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
		}
	}

}
