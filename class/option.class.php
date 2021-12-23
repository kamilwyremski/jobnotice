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

class option {

	public static function getKinds(){
		return [
			'text'=>trans('Text field'),
			'number'=>trans('Numeric field'),
			'select'=>trans('Select field'),
			'checkbox'=>trans('Checkbox')
		];
	}

	public static function addToClassified(int $classified_id=0, array $options = []){
		global $db;
		$sth = $db->prepare('DELETE from '._DB_PREFIX_.'option_value WHERE classified_id=:classified_id');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
		if(!empty($options)){
			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'option_value`(`classified_id`, `option_id`, `value`) VALUES (:classified_id, :option_id, :value)');
			$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
			foreach($options as $key=>$value){
				if(is_array($value)){
					foreach($value as $value2){
						$sth->bindValue(':option_id', $key, PDO::PARAM_INT);
						$sth->bindValue(':value', trim(strip_tags($value2)), PDO::PARAM_STR);
						$sth->execute();
					}
				}elseif($value){
					$sth->bindValue(':option_id', $key, PDO::PARAM_INT);
					$sth->bindValue(':value', trim(strip_tags($value)), PDO::PARAM_STR);
					$sth->execute();
				}
			}
		}
	}

	public static function list(int $category_id=0,string $controller=''){
		global $db;
		$options = [];
		$statement = '';
		if($controller=='search'){
			$statement .= ' AND search=1 ';
		}
		if($category_id>0){
			$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'option` WHERE (categories_all=1 OR (SELECT 1 FROM '._DB_PREFIX_.'option_category WHERE option_id='._DB_PREFIX_.'option.id AND option_category=:category_id)) '.$statement.' ORDER BY position');
			$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		}elseif($controller=='add' || $controller=='search'){
			$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'option` WHERE categories_all=1 '.$statement.' ORDER BY position');
		}else{
			$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'option` WHERE true '.$statement.' ORDER BY position');
		}
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$row['kindName'] = static::getKindName($row['kind']);
			if($row['kind']=='select' || $row['kind']=='checkbox'){
				$row['choices'] = static::getSelectChoices($row['select_choices']);
			}
			$options[] = $row;
		}
		return $options;
	}

	public static function show(int $id){
		global $db;
		$sth = $db->prepare('SELECT * FROM `'._DB_PREFIX_.'option` WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$option = $sth->fetch(PDO::FETCH_ASSOC);
		if($option){
			$option['categories'] = static::listCategories($option['id']);
		}
		return $option;
	}

	public static function listCategories(int $id){
		global $db;
		$categories = [];
		$sth = $db->prepare('SELECT option_category FROM '._DB_PREFIX_.'option_category WHERE option_id=:option_id');
		$sth->bindValue(':option_id', $id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$categories[] = $row['option_category'];
		}
		return $categories;
	}

	public static function getSelectChoices(string $choices){
		return array_unique(array_filter(array_map('trim', explode(PHP_EOL,$choices))));
	}

	public static function getKindName(string $name){
		if(isset(static::getKinds()[$name])){
			return static::getKinds()[$name];
		}
		return '';
	}

	public static function add(array $data){
		global $db;
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'option`(`name`, `position`) VALUES (:name,:position)');
		$sth->bindValue(':name', $data['name'], PDO::PARAM_STR);
		$sth->bindValue(':position', getPosition('option'), PDO::PARAM_INT);
		$sth->execute();
		static::edit($db->lastInsertId(),$data);
	}

	public static function edit(int $id,array $data){
		global $db;
		if($id>0 and static::checkKind($data['kind'])){
			if(!empty($data['select_choices'])){
				$select_choices = implode(PHP_EOL,static::getSelectChoices($data['select_choices']));
			}else{
				$select_choices = '';
			}

			$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'option` SET kind=:kind, required=:required, search=:search, categories_all=:categories_all, select_choices=:select_choices WHERE id=:id LIMIT 1');
			$sth->bindValue(':kind', $data['kind'], PDO::PARAM_STR);
			$sth->bindValue(':required', isset($data['required']), PDO::PARAM_INT);
			$sth->bindValue(':search', isset($data['search']), PDO::PARAM_INT);
			$sth->bindValue(':categories_all', isset($data['categories_all']), PDO::PARAM_INT);
			$sth->bindValue(':select_choices', $select_choices, PDO::PARAM_STR);
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();

			if(isset($data['name'])){
				$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'option` SET name=:name WHERE id=:id LIMIT 1');
				$sth->bindValue(':name', $data['name'], PDO::PARAM_STR);
				$sth->bindValue(':id', $id, PDO::PARAM_INT);
				$sth->execute();
			}

			$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'option_category` WHERE option_id=:option_id');
			$sth->bindValue(':option_id', $id, PDO::PARAM_INT);
			$sth->execute();

			if(!isset($data['categories_all']) and isset($data['categories']) and is_array($data['categories'])){
				$categories = $data['categories'];
				$values = '';
				foreach($categories as $category_id){
					if($values){$values .= ',';}
					$values .= ' ('.$id.', '.intval($category_id).') ';
				}
				$db->query('INSERT INTO `'._DB_PREFIX_.'option_category`(`option_id`, `option_category`) VALUES '.$values.'');
			}
		}
	}

	public static function checkKind(string $name){
		$kinds = static::getKinds();
		if(isset($kinds[$name])){
			return true;
		}
		return false;
	}

	public static function remove(int $id){
		global $db;
		$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'option` WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}
}
