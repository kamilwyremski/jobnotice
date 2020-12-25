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

class logsClassified {

  public static function add(int $id){
    global $db, $user;
    $sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'logs_classified`(`classified_id`, `user_id`, `ip`) VALUES (:classified_id,:user_id,:ip)');
    $sth->bindValue(':classified_id', $id, PDO::PARAM_INT);
    $sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
    $sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
    $sth->execute();
  }

  public static function list(int $limit=100){
		global $db;
    $logs_classifieds = [];
  	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS lo.*, o.slug, o.name, u.username, u.email FROM '._DB_PREFIX_.'logs_classified lo LEFT JOIN '._DB_PREFIX_.'classified o ON lo.classified_id = o.id LEFT JOIN '._DB_PREFIX_.'user u ON lo.user_id = u.id ORDER BY '.orderBy().' LIMIT :limit_from, :limit_to');
  	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
  	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
  	$sth->execute();
  	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
  		$logs_classifieds[] = $row;
  	}
  	generatePagination($limit);
    return $logs_classifieds;
	}

  public static function removeWithoutClassifieds(){
		global $db;
    $db->query('DELETE FROM '._DB_PREFIX_.'logs_classified WHERE classified_id NOT IN (SELECT id FROM '._DB_PREFIX_.'classified)');
	}

	public static function removeAll(){
		global $db;
	  $db->query('TRUNCATE `'._DB_PREFIX_.'logs_classified`');
	}
}
