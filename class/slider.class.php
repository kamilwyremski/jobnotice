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

class slider {

	public static function list(){
		global $db;
		$slider = [];
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'slider');
		foreach($sth as $row){$slider[] = $row['content'];}
		return $slider;
	}

	public static function add(){
		global $db;
		$db->exec('INSERT INTO `'._DB_PREFIX_.'slider`() VALUES ()');
	}

	public static function save(array $data){
		global $db;
		$db->query('TRUNCATE `'._DB_PREFIX_.'slider`');
		if(isset($data['content']) and is_array($data['content'])){
			$contents = array_filter($data['content']);
			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'slider`(content) VALUES (:content)');
			foreach($contents as $content){
				$sth->bindValue(':content', $content, PDO::PARAM_STR);
				$sth->execute();
			}
		}
	}

}
