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

class duration {

  public static function add(array $data){
    global $db;
    $sth = $db->query('INSERT INTO `'._DB_PREFIX_.'duration`() VALUES ()');
    $id = $db->lastInsertId();
    static::edit($id, $data);
	}

  public static function edit(int $id, array $data){
    global $db;
    $sth = $db->prepare('UPDATE `'._DB_PREFIX_.'duration` SET `length`=:length, `cost`=:cost WHERE `id`=:id LIMIT 1');
    $sth->bindValue(':length', $data['length'], PDO::PARAM_INT);
    $sth->bindValue(':cost', round(floatval($data['cost'])*100), PDO::PARAM_INT);
    $sth->bindValue(':id', $id, PDO::PARAM_INT);
    $sth->execute();
	}

  public static function remove(int $id){
    global $db;
    $sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'duration` WHERE id=:id LIMIT 1');
    $sth->bindValue(':id', $id, PDO::PARAM_INT);
    $sth->execute();
  }

  public static function list(){
  	global $db;
  	$classifieds_days = [];
  	$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'duration ORDER BY length');
  	foreach($sth as $row){
  		$classifieds_days[$row['id']] = $row;
  	}
  	return $classifieds_days;
  }

  public static function getDays(int $id=0){
  	global $db, $settings;
  	$days = ['length'=>$settings['days_default'],'cost'=>0];
  	if($id>0){
  		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'duration WHERE id=:id LIMIT 1');
  		$sth->bindValue(':id', $id, PDO::PARAM_INT);
  		$sth->execute();
  		$classifieds_days = $sth->fetch(PDO::FETCH_ASSOC);
  		if($classifieds_days){
  			$days['length'] = $classifieds_days['length'];
  			$days['cost'] = $classifieds_days['cost'];
  		}
  	}
  	return $days;
  }
}
