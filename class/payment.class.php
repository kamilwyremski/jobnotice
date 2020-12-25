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

class payment {

  public static function new(string $company,int $item_id,string $type){
  	global $db, $settings;
  	$amount = $id = 0;
  	$description = $slug = $email = '';
  	$classified = classified::show($item_id,'payment');
  	if(!empty($classified)){
  		$slug = $classified['slug'];
  		$email = $classified['email'];
  		if($type=='promote'){
  			$amount = $settings['promote_cost'];
  			$description = slug(trans('Promote classified')).' '.$classified['slug'];
  		}elseif($type=='add_classified'){
  			$amount = classified::countCost($classified['id'])['total']/100;
  			$description = slug(trans('Activation classified')).' '.$classified['slug'];
  		}
  		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'payment`(`company`, `amount`, `item_id`, `type`, `date_update`, `ip`) VALUES (:company,:amount,:item_id,:type,NOW(),:ip)');
  		$sth->bindValue(':company', $company, PDO::PARAM_STR);
  		$sth->bindValue(':amount', $amount*100, PDO::PARAM_STR);
  		$sth->bindValue(':item_id', $item_id, PDO::PARAM_INT);
  		$sth->bindValue(':type', $type, PDO::PARAM_STR);
  		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
  		$sth->execute();
  		$id = $db->lastInsertId();
  		$url = absolutePath('classified',$item_id,$slug).'?';
  		if(!$classified['user_id']){
  			$url .= 'code='.$classified['code'];
  		}
  	}
  	return ['id'=>$id,'amount'=>$amount,'description'=>$description,'slug'=>$slug,'item_id'=>$item_id,'url'=>$url,'email'=>$email];
  }

  public static function check(int $id,string $amount,array $data_payment = []){
  	global $db;
  	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'payment WHERE id=:id AND status="new" LIMIT 1');
  	$sth->bindValue(':id', $id, PDO::PARAM_INT);
  	$sth->execute();
  	$payment = $sth->fetch(PDO::FETCH_ASSOC);
  	if(!empty($payment)){
  		if($payment['amount']/100<=$amount){
  			if($payment['type']=='promote'){
  				$classified = classified::show($payment['item_id'],'payment');
  				if(!$classified['promoted']){
  					mail::send('start_promote',$classified['email'],['classified_name'=>$classified['name'], 'classified_url'=>absolutePath('classified',$classified['id'],$classified['slug'])]);
  				}
  				classified::enablePromote($payment['item_id']);
  			}elseif($payment['type']=='add_classified'){
  				$classified = classified::show($payment['item_id'],'payment');
  				if(!$classified['active']){
  					if($classified['user_id']){
  						mail::send('classified_start',$classified['email'],['classified_name'=>$classified['name'], 'classified_url'=>absolutePath('classified',$classified['id'],$classified['slug'])]);
  					}else{
  						mail::send('classified_start_not_logged',$classified['email'],['classified_edit_link'=>absolutePath('edit',$classified['id'],$classified['slug']).'?code='.$classified['code'], 'classified_activate_link'=>absolutePath('classified',$classified['id'],$classified['slug']).'?activate&code='.$classified['code'], 'classified_name'=>$classified['name'], 'classified_url'=>absolutePath('classified',$classified['id'],$classified['slug'])]);
  					}
  					classified::activate($payment['item_id']);
  				}
  			}
  		}
  		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment SET status="completed", `date_update`=NOW() WHERE id=:id LIMIT 1');
  		$sth->bindValue(':id', $id, PDO::PARAM_INT);
  		$sth->execute();
  	}else{
  		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment SET status="failed", `date_update`=NOW() WHERE id=:id LIMIT 1');
  		$sth->bindValue(':id', $id, PDO::PARAM_INT);
  		$sth->execute();
  	}
    if(!empty($data_payment)){
		$payment_id = '';
		if(!empty($data_payment['operation_number'])){
			$payment_id = $data_payment['operation_number'];
			unset($data_payment['operation_number']);
		}elseif(!empty($data_payment['txn_id'])){
			$payment_id = $data_payment['txn_id'];
			unset($data_payment['txn_id']);
		}elseif(!empty($data_payment['p24_order_id'])){
			$payment_id = $data_payment['p24_order_id'];
			unset($data_payment['p24_order_id']);
		}
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'payment SET payment_id=:payment_id, `data`=:data WHERE id=:id LIMIT 1');
  		$sth->bindValue(':payment_id', $payment_id, PDO::PARAM_STR);
    	$sth->bindValue(':data', serialize($data_payment), PDO::PARAM_STR);
    	$sth->bindValue(':id', $id, PDO::PARAM_INT);
  		$sth->execute();
    }
  }

  public static function listLogs(int $limit=100){
		global $db;
    $payments = [];
  	$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS p.*, c.slug, c.name FROM '._DB_PREFIX_.'payment AS p LEFT JOIN '._DB_PREFIX_.'classified c ON p.item_id = c.id ORDER BY '.orderBy().' LIMIT :limit_from, :limit_to');
  	$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
  	$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
  	$sth->execute();
  	while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
      $row['data'] = unserialize($row['data']);
  		$payments[] = $row;
    }
  	generatePagination($limit);
    return $payments;
	}

  public static function removeLogsWithoutClassifieds(){
		global $db;
    $db->query('DELETE FROM '._DB_PREFIX_.'payment WHERE item_id NOT IN (SELECT id FROM '._DB_PREFIX_.'classified)');
	}

	public static function removeAll(){
		global $db;
    $db->query('DELETE FROM '._DB_PREFIX_.'payment');
	}
}
