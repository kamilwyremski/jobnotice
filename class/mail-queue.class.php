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

class mailQueue {

	public static function cancelMailing(){
		global $db;
		$db->query('TRUNCATE '._DB_PREFIX_.'mails_queue');
	}

	public static function countQueue(){
		global $db;
		$sth = $db->query('SELECT COUNT(1) FROM '._DB_PREFIX_.'mails_queue');
		return $sth->fetchColumn();
	}

	public static function send(int $limit=10){
		global $db;
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'mails_queue ORDER BY priority DESC, id LIMIT '.$limit);
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$data = unserialize($row['data']);
			if(mail::send($row['action'], $row['receiver'],$data)){
				$sth2 = $db->prepare('DELETE FROM `'._DB_PREFIX_.'mails_queue` WHERE id=:id LIMIT 1');
				$sth2->bindValue(':id', $row['id'], PDO::PARAM_INT);
				$sth2->execute();
			}
		}
	}

	public static function add(string $action,string $receiver,array $data=[],int $priority=0){
		global $db;
		if($action && $receiver){
			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'mails_queue`(`receiver`, `action`, `data`, `priority`) VALUES (:receiver,:action,:data,:priority)');
			$sth->bindValue(':receiver', $receiver, PDO::PARAM_STR);
			$sth->bindValue(':action', $action, PDO::PARAM_STR);
			$sth->bindValue(':data', serialize($data), PDO::PARAM_STR);
			$sth->bindValue(':priority', $priority, PDO::PARAM_INT);
			$sth->execute();
			return true;
		}
		return false;
	}
}
