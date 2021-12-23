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

class category {

	private static $categories_list = [];

	public static function list(int $category_id=0){
		global $db;
		$categories = [];
		$sth = $db->prepare('SELECT *, COALESCE((SELECT count FROM '._DB_PREFIX_.'subcategory WHERE category_id=:category_id AND subcategory_id=id LIMIT 1),0) as number_classifieds FROM '._DB_PREFIX_.'category WHERE category_id=:category_id ORDER BY position');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){$categories[] = $row;}
		return $categories;
	}

	public static function listWithSubcategories(int $category_id = 0){
		global $db;
		$categories = static::list($category_id);
		$subcategories_id = '';
		foreach($categories as $category){
			$subcategories_id .= $category['id'].',';
		}
		$subcategories_id = rtrim($subcategories_id,',');
		if($subcategories_id){
			$sth = $db->query('SELECT c.*, COALESCE((SELECT count FROM '._DB_PREFIX_.'subcategory WHERE category_id=c.category_id AND subcategory_id=c.id LIMIT 1),0) as number_classifieds FROM '._DB_PREFIX_.'category c WHERE c.category_id IN ('.$subcategories_id.') ORDER BY c.position');
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
				foreach($categories as $key=>$category){
					if($category['id'] == $row['category_id']){
						$categories[$key]['list_subcategories'][] = $row;
					}
				}
			}
		}
		return $categories;
	}

	public static function show(int $id=0, bool $breadcrumbs = false){
		global $db;
		$category = [];
		if($id>0){
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
			$category = $sth->fetch(PDO::FETCH_ASSOC);
			if($category and $breadcrumbs){
				$category['breadcrumbs'] = static::getBreadcrumbs($category);
			}
		}
		return $category;
	}

	public static function showBySlug(string $slug, int $category_id = 0){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE slug=:slug AND category_id=:category_id LIMIT 1');
		$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		$category = $sth->fetch(PDO::FETCH_ASSOC);
		return $category;
	}

	public static function listAll(){
		self::$categories_list = [];
		static::listSubcategories();
		return self::$categories_list;
	}

	public static function listAllPlain(){
		global $db;
		$categories = [];
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'category');
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$categories[] = $row;
		}
		return $categories;
	}

	public static function listSubcategories(int $category_id=0,int $depth=0){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE category_id=:category_id ORDER BY position, name');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$depth++;
			$row['depth'] = $depth;
			self::$categories_list[$row['id']] = $row;
			static::listSubcategories($row['id'],$depth);
			$depth--;
		}
	}

	public static function getBreadcrumbs(array $category){
		global $db;
		$breadcrumbs[] = $category;
		$navigation_overcategory = $category['category_id'];
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE id=:navigation_overcategory LIMIT 1');
		while($navigation_overcategory!=0){
			$sth->bindValue(':navigation_overcategory', $navigation_overcategory, PDO::PARAM_INT);
			$sth->execute();
			$overcategory = $sth->fetch(PDO::FETCH_ASSOC);
			if($overcategory){
				$navigation_overcategory = $overcategory['category_id'];
				$breadcrumbs[] = $overcategory;
			}else{
				$navigation_overcategory = 0;
			}
		}
		return(array_reverse($breadcrumbs));
	}

	public static function showFromPath(string $path){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE path=:path LIMIT 1');
		$sth->bindValue(':path', $path, PDO::PARAM_STR);
		$sth->execute();
		$category = $sth->fetch(PDO::FETCH_ASSOC);
		return $category;
	}

	public static function refreshAllSubcategories(int $category_id=0){
		global $db;
		if($category_id){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'subcategory WHERE category_id=:category_id OR ( category_id=0 AND subcategory_id=:category_id)');
			$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
			$sth->execute();
			$over_category = static::show($category_id)['category_id'];
		}else{
			$db->query('TRUNCATE `'._DB_PREFIX_.'subcategory`');
			$over_category = 0;
		}
		static::countInSubcategories($category_id);

		$count = 0;
		$query_string = '';
		foreach(self::$categories_list as $category){
			if($query_string){$query_string .= ',';}
			$query_string .= ' ('.$category['category_id'].','.$category['subcategory_id'].','.$category['count'].') ';
			if($category['category_id']==$category_id){$count += $category['count'];}
		}

		if($query_string){$query_string .= ',';}
		$query_string .= ' ('.$over_category.','.$category_id.','.($count+static::countInCategory($category_id)).') ';

		$db->query('INSERT INTO `'._DB_PREFIX_.'subcategory`(`category_id`, `subcategory_id`, `count`) VALUES '.$query_string);
	}

	public static function countInCategory(int $category_id=0){
		global $db;
		$sth = $db->prepare('SELECT COUNT(1) as count FROM '._DB_PREFIX_.'classified WHERE active=1 AND category_id=:category_id');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetchColumn();
	}

	public static function countInSubcategories(int $category_id=0){
		global $db;
		$count = 0;
		$subcategories = [];
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'category WHERE category_id=:category_id');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->execute();
		foreach($sth as $row){
			$count_classifieds = static::countInCategory($row['id']);
			$return = static::countInSubcategories($row['id']);
			$subcategories[$row['id']] = $count_classifieds + $return['count'];
			foreach($return['subcategories'] as $subcategory_id=>$subcategory_count){
				$subcategories[$subcategory_id] = $subcategory_count;
			}
			$count = $count + $count_classifieds + $return['count'];
		}
		foreach($subcategories as $subcategory_id=>$subcategory_count){
			array_push(self::$categories_list,['category_id'=>$category_id, 'subcategory_id'=>$subcategory_id,'count'=>$subcategory_count]);
		}
		return ['count'=>$count, 'subcategories'=>$subcategories];
	}

	public static function refreshCount(int $category_id=0){
		global $db;
		self::$categories_list = [];
		if($category_id>0){
			$category = static::show($category_id, true);
			$category_id = $category['breadcrumbs'][0]['id'];
			$return = static::countInSubcategories($category_id);
			array_push(self::$categories_list,['category_id'=>0, 'subcategory_id'=>$category_id,'count'=>$return['count']+static::countInCategory($category_id)]);
		}else{
			static::countInSubcategories();
		}
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'subcategory` SET `count`=:count WHERE `category_id`=:category_id AND `subcategory_id`=:subcategory_id LIMIT 1');
		foreach(self::$categories_list as $subcategory){
			$sth->bindValue(':category_id', $subcategory['category_id'], PDO::PARAM_INT);
			$sth->bindValue(':subcategory_id', $subcategory['subcategory_id'], PDO::PARAM_INT);
			$sth->bindValue(':count', $subcategory['count'], PDO::PARAM_INT);
			$sth->execute();
		}
	}

	public static function add(array $data,int $category_id=0){
		global $db;
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'category`(`category_id`, `position`, `slug`, `name`) VALUES (:category_id,:position,:slug,:name)');
		$sth->bindValue(':category_id', $category_id, PDO::PARAM_INT);
		$sth->bindValue(':position', getPosition('category', 'category_id='.$category_id), PDO::PARAM_INT);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
		$sth->execute();

		$id = $db->lastInsertId();
		static::edit($data,$id);

		$category = static::show($id, true);

		$over_categories = [];
		if(!empty($category['breadcrumbs'])){
			foreach($category['breadcrumbs'] as $value){
				$over_categories[] = $value['category_id'];
			}
		}
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'subcategory`(`category_id`, `subcategory_id`) VALUES (:category_id,'.$id.')');
		foreach($over_categories as $value){
			$sth->bindValue(':category_id', $value, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	public static function edit(array $data,int $id){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET slug=:slug, name=:name, cost=:cost, thumb=:thumb, content=:content, h1=:h1, title=:title, keywords=:keywords, description=:description WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':cost', round(floatval($data['cost'])*100), PDO::PARAM_INT);
		$sth->bindValue(':thumb', $data['thumb'], PDO::PARAM_STR);
		$sth->bindValue(':content', $data['content'], PDO::PARAM_STR);
		$sth->bindValue(':h1', $data['h1'], PDO::PARAM_STR);
		$sth->bindValue(':title', $data['title'], PDO::PARAM_STR);
		$sth->bindValue(':keywords', $data['keywords'], PDO::PARAM_STR);
		$sth->bindValue(':description', $data['description'], PDO::PARAM_STR);
		$sth->execute();
		static::refreshPath($id);
	}

	public static function refreshPath(int $id){
		global $db;
		$category = static::show($id, true);
		if($category){
			$over_categories = [];
			foreach($category['breadcrumbs'] as $key => $value){
				$over_categories[] = $value['slug'];
			}
			$path = implode('/',$over_categories);

			if($path!=$category['path']){

				$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET path=:path WHERE id=:id LIMIT 1');
				$sth->bindValue(':id', $id, PDO::PARAM_INT);
				$sth->bindValue(':path', $path, PDO::PARAM_STR);
				$sth->execute();

				self::$categories_list = [];

				static::listSubcategories($id);

				$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'category` SET path=:path WHERE id=:id LIMIT 1');
				foreach(self::$categories_list as $subcategory){
					$category = static::show($subcategory['id'], true);
					$over_categories = [];
					foreach($category['breadcrumbs'] as $key => $value){
						$over_categories[] = $value['slug'];
					}
					$path = implode('/',$over_categories);
					$sth->bindValue(':id', $subcategory['id'], PDO::PARAM_INT);
					$sth->bindValue(':path', $path, PDO::PARAM_STR);
					$sth->execute();
				}
			}
		}
	}

	public static function remove(int $id){
		global $db;
		$category = static::show($id);
		if($category){
			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'classified SET category_id="0" WHERE category_id=:category_id');
			$sth->bindValue(':category_id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'subcategory WHERE subcategory_id=:id');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'category WHERE category_id=:id');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			foreach($sth as $row){
				static::remove($row['id']);
			}
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'category WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $category['id'], PDO::PARAM_INT);
			$sth->execute();
			static::refreshCount($category['category_id']);
		}
	}
}
