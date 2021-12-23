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

class classified {

	public static function list(int $limit=10,array $data=[],string $type='all'){
		global $db, $settings, $user;
		$where_statement = ' true ';
		$having_statement = ' true ';
		$select = '';
		$join = '';
		$bind_values = [];
		if(isset($data['category']) and $data['category']>0){
			$where_statement .= ' AND (c.category_id=:category OR c.category_id = any(SELECT subcategory_id FROM '._DB_PREFIX_.'subcategory WHERE category_id=:category)) ';
			$bind_values['category'] = $data['category'];
		}
		if(!empty($data['type'])){
			$where_statement .= ' AND c.type_id = (SELECT id FROM '._DB_PREFIX_.'type WHERE slug=:type LIMIT 1) ';
			$bind_values['type'] = $data['type'];
		}
		if(!empty($data['state'])){
			$where_statement .= ' AND c.state_id = (SELECT id FROM '._DB_PREFIX_.'state WHERE slug=:state LIMIT 1) ';
			$bind_values['state'] = $data['state'];
			if(!empty($data['state2'])){
				$where_statement .= ' AND c.state2_id = (SELECT id FROM '._DB_PREFIX_.'state WHERE slug=:state2 LIMIT 1) ';
				$bind_values['state2'] = $data['state2'];
			}
		}
		if(isset($data['search'])){
			if(isset($data['id']) and $data['id']>0){
				$where_statement .= ' AND c.id = :id ';
				$bind_values['id'] = $data['id'];
			}
			if(isset($data['not_id']) and $data['not_id']>0){
				$where_statement .= ' AND c.id != :not_id ';
				$bind_values['not_id'] = $data['not_id'];
			}
			if(!empty($data['username'])){
				$where_statement .= ' AND c.user_id = (SELECT id FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1) ';
				$bind_values['username'] = $data['username'];
			}
			if(!empty($data['username_or_email'])){
				$where_statement .= ' AND c.user_id in (SELECT id FROM '._DB_PREFIX_.'user WHERE username LIKE :username_or_email OR email LIKE :username_or_email) ';
				$bind_values['username_or_email'] = '%'.$data['username_or_email'].'%';
			}
			if(!empty($data['name'])){
				$names = explode(' ', $data['name']);
				$where_statement .= ' AND ( ';
				for($i=0; $i < count($names); $i++){
					$where_statement .= ' c.slug LIKE :name_'.$i.' ';
					if($i!=(count($names)-1)){$where_statement .= ' OR ';}
					$bind_values['name_'.$i] = '%'.slug($names[$i]).'%';
				}
				$where_statement .= ' ) ';
			}
			if(!empty($data['keywords'])){
				if(isset($data['exact_phrase'])){
					$where_statement .= ' AND (c.slug LIKE :keywords_slug OR c.description LIKE :keywords) ';
					$bind_values['keywords_slug'] = '%'.slug($data['keywords']).'%';
					$bind_values['keywords'] = '%'.$data['keywords'].'%';
				}else{
					$select .=', MATCH(c.name,c.slug,c.description) AGAINST(:keywords) AS score';
					$bind_values['keywords'] = slug($data['keywords']).' '.$data['keywords'];
					$having_statement .= ' AND score>0 ';
				}
			}
			if(isset($data['active'])){
				if($data['active']=='yes'){
					$where_statement .= ' AND c.active="1" ';
				}elseif($data['active']=='no'){
					$where_statement .= ' AND c.active="0" ';
				}
			}
			if(isset($data['promoted'])){
				if($data['promoted']=='yes'){
					$where_statement .= ' AND c.promoted="1" ';
				}elseif($data['promoted']=='no'){
					$where_statement .= ' AND c.promoted="0" ';
				}
			}
			if(!empty($data['address'])){
				if(isset($data['distance']) and $data['distance']>0){
					$coordinates = getCoordinates($data['address']);
					$select .= ' , (6371 * acos( cos( radians('.$coordinates['lat'].')) * cos( radians(c.address_lat) ) * cos( radians(c.address_long) - radians('.$coordinates['long'].')) + sin(radians('.$coordinates['lat'].')) * sin(radians(c.address_lat)))) AS distance ';
					$where_statement .= ' AND c.address_lat!=0 AND c.address_long!=0 ';
					$having_statement .= ' AND distance <= :distance ';
					$bind_values['distance'] = $data['distance'];
				}else{
					$where_statement .= ' AND c.address LIKE :address ';
					$bind_values['address'] = '%'.$data['address'].'%';
				}
			}
			if(isset($data['salary_from']) and $data['salary_from']>0){
				$where_statement .= ' AND c.salary>=:salary_from ';
				$bind_values['salary_from'] = $data['salary_from']*100;
			}
			if(isset($data['salary_to']) and $data['salary_to']>0){
				$where_statement .= ' AND c.salary<=:salary_to ';
				$bind_values['salary_to'] = $data['salary_to']*100;
			}
			if(isset($data['options']) and is_array($data['options'])){
				$options = array_filter($data['options']);
				foreach($options as $key => $value){
					if(is_array($value)){
						$options[$key] = array_filter($value);
					}
				}
				$options = array_filter($options);
				if(!empty($options)){
					$where_statement .= ' AND (';
					$last = count($options);
					$i = 0;
					foreach($options as $key => $value){
						if(is_array($value)){
							$where_statement .= ' ( ';
							if(isset($value['from']) || isset($value['to'])){
								if(isset($value['from']) and $value['from']>=0){
									$where_statement .= ' (SELECT count(1) FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE op.id=ov.option_id AND ov.option_id=:option_id_'.$i.'_key AND ov.classified_id=c.id AND CAST(ov.value AS UNSIGNED) >=:option_id_'.$i.'_from LIMIT 1) > 0 ';
									$bind_values['option_id_'.$i.'_key'] = $key;
									$bind_values['option_id_'.$i.'_from'] = $value['from'];
								}
								if(isset($value['from']) and $value['from']>=0 and isset($value['to']) and $value['to']>=0){$where_statement .= ' AND ';}
								if(isset($value['to']) and $value['to']>=0){
									$where_statement .= ' (SELECT count(1) FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE op.id=ov.option_id AND ov.option_id=:option_id_'.$i.'_key AND ov.classified_id=c.id AND CAST(ov.value AS UNSIGNED) <=:option_id_'.$i.'_to LIMIT 1) > 0 ';
									$bind_values['option_id_'.$i.'_key'] = $key;
									$bind_values['option_id_'.$i.'_to'] = $value['to'];
								}
							}else{
								$last2 = count($value);
								$j = 0;
								foreach($value as $key2 => $value2){
									$where_statement .= ' (SELECT count(1) FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE op.id=ov.option_id AND ov.option_id=:option_id_'.$i.'_'.$j.'_key AND ov.classified_id=c.id AND ov.value=:option_id_'.$i.'_'.$j.'_value LIMIT 1) > 0 ';
									$bind_values['option_id_'.$i.'_'.$j.'_key'] = $key;
									$bind_values['option_id_'.$i.'_'.$j.'_value'] = $value2;
									if($j!=$last2-1){$where_statement .= ' OR ';}
									$j++;
								}
							}
							$where_statement .= ' ) ';
						}else{
							$where_statement .= ' (SELECT count(1) FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE op.id=ov.option_id AND ov.option_id=:option_id_'.$i.'_key AND ov.classified_id=o.id AND ov.value LIKE :option_id_'.$i.'_value LIMIT 1) > 0 ';
							$bind_values['option_id_'.$i.'_key'] = $key;
							$bind_values['option_id_'.$i.'_value'] = $value;
						}
						if($i!=$last-1){$where_statement .= ' AND ';}
						$i++;
					}
					$where_statement .= ') ';
				}
			}
			if(!empty($data['date_from'])){
				$where_statement .= ' AND c.date >= :date_from ';
				$bind_values['date_from'] = $data['date_from'];
			}
			if(!empty($data['date_to'])){
				$where_statement .= ' AND c.date <= :date_to ';
				$bind_values['date_to'] = $data['date_to'].' 23:59:59 ';
			}
			if(!empty($data['date_finish_from'])){
				$where_statement .= ' AND c.date_finish >= :date_finish_from ';
				$bind_values['date_finish_from'] = $data['date_finish_from'];
			}
			if(!empty($data['date_finish_to'])){
				$where_statement .= ' AND c.date_finish <= :date_finish_to';
				$bind_values['date_finish_to'] = $data['date_finish_to'];
			}
			if(!empty($data['ip'])){
				$where_statement .= ' AND c.ip like :ip ';
				$bind_values['ip'] = '%'.$data['ip'].'%';
			}
			if(isset($data['classifieds_with_photos'])){
				$having_statement .= ' AND thumb!="" ';
			}
		}

		$sort = ' c.date_start DESC ';
		if(!empty($data['sort'])){
			if($data['sort']=='random'){
				$sort = ' rand() ';
			}elseif($data['sort']=='newest'){
				$sort = ' c.date_start desc ';
			}elseif($data['sort']=='oldest'){
				$sort = ' c.date_start ';
			}elseif($data['sort']=='cheapest'){
				$sort = ' c.salary, c.date_start DESC ';
			}elseif($data['sort']=='most_expensive'){
				$sort = ' c.salary DESC, c.date_start DESC ';
			}elseif($data['sort']=='a-z'){
				$sort = ' c.name, c.date_start DESC ';
			}elseif($data['sort']=='z-a'){
				$sort = ' c.name DESC, c.date_start DESC ';
			}elseif($data['sort']=='nearest'){
				if(strpos($having_statement, 'distance')){
					$sort = ' distance, c.date_start DESC ';
				}
			}elseif($data['sort']=='farthest'){
				if(strpos($having_statement, 'distance')){
					$sort = ' distance DESC, c.date_start DESC ';
				}
			}elseif($data['sort']=='most_accurate'){
				if(strpos($having_statement, 'score')){
					$sort = ' score DESC ';
				}
			}else{
				$sort = orderBy();
			}
		}elseif(strpos($having_statement, 'score')){
			$sort = ' score DESC ';
		}

		if($type=='my_classifieds'){
			$where_statement .= ' AND c.user_id='.$user->getId().' ';
		}elseif($type=='clipboard'){
			$where_statement .= ' AND c.active=1 AND (SELECT 1 FROM '._DB_PREFIX_.'clipboard WHERE classified_id=c.id AND user_id='.$user->id.') ';
		}elseif($type=='index_page'){
			$where_statement .= ' AND c.active=1 ';
		}elseif($type=='classifieds'){
			$where_statement .= ' AND c.active=1 ';
		}elseif($type=='admin'){
			$select .= ', u.username AS username, (SELECT count(1) FROM '._DB_PREFIX_.'logs_classified WHERE classified_id=c.id) AS view_all, (SELECT count(Distinct lo.ip) FROM '._DB_PREFIX_.'logs_classified lo WHERE classified_id=c.id) AS view_unique ';
			$join .= ' LEFT JOIN '._DB_PREFIX_.'user u ON c.user_id = u.id ';
		}
		if($type=='classifieds' or $type=='my_classifieds' or $type=='clipboard'){
			$select .= ', cat.name AS category_name, s.name AS state_name, s.slug AS state_slug, s2.name AS state2_name, s2.slug AS state2_slug, t.name AS type_name, t.slug AS type_slug ';
			$join .= 'LEFT JOIN '._DB_PREFIX_.'category cat ON c.category_id = cat.id
			LEFT JOIN '._DB_PREFIX_.'state s ON c.state_id = s.id
			LEFT JOIN '._DB_PREFIX_.'state s2 ON c.state2_id = s2.id
			LEFT JOIN '._DB_PREFIX_.'type t ON c.type_id = t.id
			LEFT JOIN '._DB_PREFIX_.'user u ON c.user_id = u.id ';
		}
		if($type=='index_page'){
			$select .= ', s.name AS state_name, s.slug AS state_slug, s2.name AS state2_name, s2.slug AS state2_slug, u.name AS company_name, u.username ';
			$join .= ' 
			LEFT JOIN '._DB_PREFIX_.'state s ON c.state_id = s.id
			LEFT JOIN '._DB_PREFIX_.'state s2 ON c.state2_id = s2.id
			LEFT JOIN '._DB_PREFIX_.'user u ON c.user_id = u.id ';
		}
		if($type!='admin'){
			if($sort == ' c.date_start desc '){
				$sort = ' if(c.promoted="1", c.promoted_date_start, 0) DESC, '.$sort;
			}else{
				$sort = ' c.promoted DESC, '.$sort;
			}
		}

		$classifieds = [];

		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS c.* '.$select.',
			(SELECT CONCAT(folder,thumb) FROM '._DB_PREFIX_.'photo WHERE classified_id=c.id ORDER BY position LIMIT 1) AS thumb FROM '._DB_PREFIX_.'classified c '.$join.'
			WHERE '.$where_statement.' HAVING '.$having_statement.' ORDER BY '.$sort.' LIMIT :limit_from, :limit_to');

		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		foreach($bind_values as $key => $value){
			$sth->bindValue(':'.$key, $value, PDO::PARAM_STR);
		}
		$sth->execute();
		generatePagination($limit);
		while($row = $sth->fetch(PDO::FETCH_ASSOC)){
			if($type=='my_classifieds' and $settings['allow_refresh_classifieds']){
				$row['refresh']['active'] = false;
				$row['refresh']['days'] = ceil((strtotime($row['date_finish']) - time())/(60*60*24)-$settings['days_before_refresh']);
				if($row['active']==0 or $row['refresh']['days']<=0){
					$cost_classified = static::countCost($row['id']);
					if($cost_classified['total']){
						$row['refresh']['must_payed'] = true;
					}elseif(!$settings['automatically_activate_classifieds'] and !$row['admin_confirmed']){
						$row['refresh']['not_confirmed'] = true;
					}else{
						$row['refresh']['active'] = true;
					}
				}
			}
			$classifieds[] = $row;
		}
		return $classifieds;
	}

	public static function add(array $data){
		global $db, $user, $settings;
		$code = bin2hex(random_bytes(32));
		if(empty($data['category_id'])){$data['category_id']=0;}
		if(empty($data['duration_id'])){$data['duration_id']=0;}

		if($user->getId()){
			$email_confirmed = 1;
		}else{
			$email_confirmed = 0;
		}

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'classified`(`user_id`, `email_confirmed`, `name`, `slug`, `code`, `ip`, `date_finish`) VALUES (:user_id,:email_confirmed,:name,:slug,:code,:ip,(CURDATE() + INTERVAL :days_to_finish DAY))');
		$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
		$sth->bindValue(':email_confirmed', $email_confirmed, PDO::PARAM_INT);
		$sth->bindValue(':name', $data['name'], PDO::PARAM_STR);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->bindValue(':days_to_finish', duration::getDays($data['duration_id'])['length'], PDO::PARAM_INT);
		$sth->execute();
		$id = $db->lastInsertId();

		$classified = static::edit($id,$data);

		$classified_cost = static::countCost($id);
		if($classified_cost['total']){
			$active = 0;
			$send_mail = false;
		}elseif($user->getId()){
			$active = $settings['automatically_activate_classifieds'];
			$send_mail = true;
		}else{
			$active = 0;
			$send_mail = true;
		}

		if($active){
			static::activate($id, date('Y-m-d', strtotime(date("Y-m-d"). ' + '.duration::getDays($data['duration_id'])['length'].' days')));
		}
		
		if($send_mail){
			if($user->getId()){
				mail::send('classified_start',$data['email'],['classified_name'=>$data['name'], 'classified_url'=>absolutePath('classified',$id,$classified['slug']),'user_id'=>$user->getId()]);
			}else{
				mail::send('classified_start_not_logged',$data['email'],[
					'classified_edit_link'=>absolutePath('edit',$id,$classified['slug']).'?code='.$code, 'classified_activate_link'=>absolutePath('classified',$id,$classified['slug']).'?activate&code='.$code,
					'classified_name'=>$data['name'],
					'classified_url'=>absolutePath('classified',$id,$classified['slug'])]);
			}
		}
		return ['id'=>$id, 'slug'=>$classified['slug'], 'active'=>$active, 'code'=>$code];
	}

	public static function edit(int $id,array $data,bool $checkCost=false){
		global $db, $settings, $user, $purifier;

		if($checkCost){
			$old_price = static::countCost($id)['total'];
		}

		if(!empty($data['address'])){$data['address'] = trim(strip_tags($data['address']));}else{$data['address'] = '';}
		if(empty($data['category_id'])){$data['category_id']=0;}
		if(empty($data['state_id'])){$data['state_id']=0;}
		if(empty($data['state2_id'])){$data['state2_id']=0;}
		if(empty($data['type_id'])){$data['type_id']=0;}
		if(!empty($data['address_lat'])){$data['address_lat'] = strval($data['address_lat']);}else{$data['address_lat'] = 0;}
		if(!empty($data['address_long'])){$data['address_long'] = strval($data['address_long']);}else{$data['address_long'] = 0;}
		if(isset($data['salary']) and $data['salary']>0){
			$data['salary'] = round(floatval($data['salary'])*100);
		}else{
			$data['salary'] = 0;
		}
		$salary_negotiate = isset($data['salary_negotiate']);

		if($user->logged_in and !$user->moderator){
			$data['email'] = $user->email;
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET name=:name, slug=:slug, address=:address, phone=:phone, facebook_url=:facebook_url, email=:email, category_id=:category_id, state_id=:state_id, state2_id=:state2_id, type_id=:type_id, description=:description, address_lat=:address_lat, address_long=:address_long, salary=:salary, salary_negotiate=:salary_negotiate, salary_net=:salary_net WHERE id=:id LIMIT 1');
		$name = substr(trim(settings::checkWordsBlackList(strip_tags($data['name']))),0,$settings['number_char_title']);
		$slug = slug($name);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
		$sth->bindValue(':address', $data['address'], PDO::PARAM_STR);
		$sth->bindValue(':phone', trim(strip_tags($data['phone'])), PDO::PARAM_STR);
		$sth->bindValue(':facebook_url', trim(strip_tags($data['facebook_url'])), PDO::PARAM_STR);
		$sth->bindValue(':email', trim(strip_tags($data['email'])), PDO::PARAM_STR);
		$sth->bindValue(':category_id', $data['category_id'], PDO::PARAM_INT);
		$sth->bindValue(':state_id', $data['state_id'], PDO::PARAM_INT);
		$sth->bindValue(':state2_id', $data['state2_id'], PDO::PARAM_INT);
		$sth->bindValue(':type_id', $data['type_id'], PDO::PARAM_INT);
		$sth->bindValue(':description', settings::checkWordsBlackList(nofollow($purifier->purify(trim($data['description'])))), PDO::PARAM_STR);
		$sth->bindValue(':address_lat', $data['address_lat'], PDO::PARAM_STR);
		$sth->bindValue(':address_long', $data['address_long'], PDO::PARAM_STR);
		$sth->bindValue(':salary', $data['salary'], PDO::PARAM_INT);
		$sth->bindValue(':salary_negotiate', $salary_negotiate, PDO::PARAM_INT);
		$sth->bindValue(':salary_net', strip_tags($data['salary_net']), PDO::PARAM_STR);
		$sth->execute();

		if(!empty($data['duration_id'])){
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET duration_id=:duration_id, date_finish=(NOW() + INTERVAL :days_to_finish DAY) WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->bindValue(':duration_id', $data['duration_id'], PDO::PARAM_INT);
			$sth->bindValue(':days_to_finish', duration::getDays($data['duration_id'])['length'], PDO::PARAM_INT);
			$sth->execute();
		}

		if($checkCost){
			$new_price = static::countCost($id)['total'];
			if($new_price>$old_price){
				static::deactivate($id);
			}
		}

		photo::addToClassified($id, isset($data['photos']) ? $data['photos'] : []);

		option::addToClassified($id, isset($data['options']) ? $data['options'] : []);

		return ['id'=>$id, 'slug'=>$slug];
	}

	public static function show(int $id,string $controller=''){
		global $db, $user;
		$join = '';
		$select = '';
		if($controller == 'classified'){
			$select .= ', cat.name as category_name, s.name as state_name, s.slug as state_slug, s2.name as state2_name, s2.slug as state2_slug, t.name as type_name, t.slug as type_slug, u.username as username, u.avatar as user_avatar, u.nip AS user_nip, u.name AS company_name, (SELECT count(1) FROM '._DB_PREFIX_.'logs_classified WHERE classified_id=c.id) AS view_all, (SELECT count(Distinct lo.ip) FROM '._DB_PREFIX_.'logs_classified lo WHERE classified_id=c.id) AS view_unique ';
			$join .= 'LEFT JOIN '._DB_PREFIX_.'category cat ON c.category_id = cat.id
			LEFT JOIN '._DB_PREFIX_.'state s ON c.state_id = s.id
			LEFT JOIN '._DB_PREFIX_.'state s2 ON c.state2_id = s2.id
			LEFT JOIN '._DB_PREFIX_.'type t ON c.type_id = t.id
			LEFT JOIN '._DB_PREFIX_.'user u ON c.user_id = u.id ';
			if($user->getId()){
				$select .= ', (SELECT count(1) FROM '._DB_PREFIX_.'clipboard WHERE user_id='.$user->getId().' AND classified_id=c.id LIMIT 1) AS clipboard';
			}
		}
		$sth = $db->prepare('SELECT c.* '.$select.' FROM '._DB_PREFIX_.'classified c '.$join.' WHERE c.id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$classified = $sth->fetch(PDO::FETCH_ASSOC);
		if($classified){
			if($controller!='add_similar' and $controller!='payment'){
				$classified['photos'] = photo::list($id);
			}

			if($controller=='edit' || $controller=='add_similar'){

				$category = category::show($classified['category_id'], true);
				$classified['categories'] = [];
				if(!empty($category['breadcrumbs'])){
					foreach($category['breadcrumbs'] as $breadcrumb){
						$classified['categories'][] = $breadcrumb['id'];
					}
				}

				$sth = $db->prepare('SELECT name, id, value FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE ov.option_id = op.id AND ov.classified_id=:classified_id');
				$sth->bindValue(':classified_id', $id, PDO::PARAM_INT);
				$sth->execute();
				while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
					$classified['options'][$row['id']][] = $row['value'];
				}

			}elseif($controller=='classified'){

				logsClassified::add($id);

				$sth = $db->prepare('SELECT op.name, ov.value FROM '._DB_PREFIX_.'option_value ov, `'._DB_PREFIX_.'option` op WHERE ov.option_id = op.id AND ov.classified_id=:classified_id');
				$sth->bindValue(':classified_id', $id, PDO::PARAM_INT);
				$sth->execute();
				while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
					$classified['options'][$row['name']][] = $row['value'];
				}

				$category_id = $classified['category_id'];
				$classified['categories'] = [];
				do{
					$category = category::show($category_id);
					if($category){
						$classified['categories'][] = $category;
						$category_id = $category['category_id'];
					}else{
						$category_id = 0;
					}
				}while($category_id);
				$classified['categories'] = array_reverse($classified['categories']);

				if(!$classified['view_all']){
					$classified['view_all'] = 1;
				}
				if(!$classified['view_unique']){
					$classified['view_unique'] = 1;
				}
			}
			if($controller=='add_similar'){
				$classified['add_similar'] = true;
			}
			return $classified;
		}
	}

	public static function checkActive(int $id,string $code=''){
		global $db, $user;
		if($user->moderator){
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE id=:id LIMIT 1');
		}elseif($code){
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE code=:code AND id=:id LIMIT 1');
			$sth->bindValue(':code', $code, PDO::PARAM_STR);
		}else{
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE (active=1 OR (user_id=:user_id AND user_id>0)) AND id=:id LIMIT 1');
			$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
		}
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		if($sth->fetchColumn()){
			return true;
		}
		return false;
	}

	public static function activateCode(string $code){
		global $db, $settings;
		$sth = $db->prepare('SELECT id, email FROM '._DB_PREFIX_.'classified WHERE active=0 AND user_id=0 AND code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		$classified = $sth->fetch(PDO::FETCH_ASSOC);
		if($classified){
			$user_id = user::getIdFromEmail($classified['email']);
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET email_confirmed=1, user_id=:user_id WHERE id=:id LIMIT 1');
			$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
			$sth->bindValue(':id', $classified['id'], PDO::PARAM_INT);
			$sth->execute();
			$_SESSION['flash'] = 'email_confirmed';
			if($settings['automatically_activate_classifieds'] and static::countCost($classified['id'])['total']==0){
				static::activate($classified['id']);
				static::refreshCountCategories($classified['id']);
				$_SESSION['flash'] = 'classified_activated';
			}
		}
	}

	public static function checkPermissions(int $id,string $code=''){
		global $user, $db;
		if($user->getId()){
			if($user->moderator){
				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE id=:id LIMIT 1');
			}else{
				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE id=:id AND user_id=:user_id LIMIT 1');
				$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
			}
		}else{
			if(empty($code)){
				return false;
			}else{
				$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'classified WHERE id=:id AND code=:code LIMIT 1');
				$sth->bindValue(':code', $code, PDO::PARAM_STR);
			}
		}
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		if($sth->fetchColumn()){return true;}
		return false;
	}

	public static function deactivate(int $id){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'classified` SET active=0 WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		static::refreshCountCategories($id);
	}

	public static function activate(int $id,string $date_finish='',bool $admin_confirmed=false){
		global $db, $settings;
		if(!$date_finish){
			$classified = classified::show($id);
			$date_finish = date('Y-m-d', strtotime(date("Y-m-d"). ' + '.duration::getDays($classified['duration_id'])['length'].' days'));
		}
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET active=1, date_start=NOW(), date_finish=:date_finish, admin_confirmed=:admin_confirmed WHERE id=:id LIMIT 1');
		$sth->bindValue(':date_finish', $date_finish, PDO::PARAM_STR);
		$sth->bindValue(':admin_confirmed', $admin_confirmed, PDO::PARAM_INT);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		static::refreshCountCategories($id);
	}

	public static function disablePromote(int $id){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'classified` SET promoted=0 WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function enablePromote(int $id,string $date=''){
		global $db, $settings;
		$classified = static::show($id);
		$date_finish = date("Y-m-d", strtotime($classified['date_finish']));
		if($date){
			$promoted_date_finish = $date;
		}elseif($classified['promoted'] and $classified['promoted_date_finish'] > date("Y-m-d")){
			$promoted_date_finish = date('Y-m-d', strtotime($classified['promoted_date_finish']. ' + '.$settings['promote_days'].' days'));
		}else{
			$promoted_date_finish = date('Y-m-d', strtotime(date("Y-m-d"). ' + '.$settings['promote_days'].' days'));
		}
		if($date_finish < $promoted_date_finish){
			$date_finish = $promoted_date_finish;
		}
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET promoted=1, promoted_date_start=NOW(), promoted_date_finish=:promoted_date_finish, date_finish=:date_finish WHERE id=:id LIMIT 1');
		$sth->bindValue(':promoted_date_finish', $promoted_date_finish, PDO::PARAM_STR);
		$sth->bindValue(':date_finish', $date_finish, PDO::PARAM_STR);
		$sth->bindValue(':id', $classified['id'], PDO::PARAM_INT);
		$sth->execute();
	}

	public static function refresh(int $id){
		global $db, $user, $settings;
		if($settings['automatically_activate_classifieds']){
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'classified` SET active = 1, date_start=NOW(), date_finish=(CURDATE() + INTERVAL '.$settings['days_refresh'].' DAY) WHERE id=:id AND user_id=:user_id LIMIT 1');
		}else{
			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'classified` SET active = 1, date_start=NOW(), date_finish=(CURDATE() + INTERVAL '.$settings['days_refresh'].' DAY) WHERE id=:id AND user_id=:user_id AND admin_confirmed=1 LIMIT 1');
		}
		$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		static::refreshCountCategories($id);
	}

	public static function refreshCountCategories(int $id){
		$classified = static::show($id);
		if($classified['category_id']){
			category::refreshCount($classified['category_id']);
		}
	}

	public static function listSimilar(array $classified, int $limit=10){
		global $db;
		$data['search'] = true;
		$data['not_id'] = $classified['id'];
		if($classified['category_id']){
			$data['category'] = $classified['category_id'];
		}
		return static::list($limit,$data,'classifieds');
	}

	public static function countCost(int $id){
		global $db, $settings;
		$costs = [];
		$classified = static::show($id);
		if($classified){
			$costs['total'] = $settings['add_cost']*100;
			$costs['list'][] = ['name'=>trans('The cost of issuing classified'),'value'=>$settings['add_cost']*100];
			$category = category::show($classified['category_id']);
			if($category){
				$costs['list'][] = ['name'=>trans('The cost for issuing an classified in the category').' '.$category['name'],'value'=>$category['cost']];
				$costs['total'] += $category['cost'];
			}
			if($classified['duration_id']){
				$duration = duration::getDays($classified['duration_id']);
				$costs['list'][] = ['name'=>trans('The cost for the duration of classified').' ('.$duration['length'].' '.trans('days').')','value'=>$duration['cost']];
				$costs['total'] += $duration['cost'];
			}
		}
		return $costs;
	}

	public static function listNames(string $name){
		global $db;
		$names = [];
		$sth = $db->prepare('SELECT DISTINCT name FROM '._DB_PREFIX_.'classified WHERE slug LIKE :name AND active=1 LIMIT 6');
		$sth->bindValue(':name', '%'.slug($name).'%', PDO::PARAM_STR);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$names[] = $row['name'];
		}
		return $names;
	}

	public static function checkAddClassified(){
		global $settings, $user;
		if($settings['add_classifieds']=='only_employer'){
			if($user->getId() and $user->type=='Employer'){
				return true;
			}else{
				return false;
			}
		}elseif($settings['add_classifieds']=='only_logged'){
			if($user->getId()){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}

	public static function remove(int $id){
		global $db;
		$classified = static::show($id);
		if($classified){
			photo::removeInClassified($id);
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'classified WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
			if($classified['category_id']){
				category::refreshCount($classified['category_id']);
			}
		}
	}
}
