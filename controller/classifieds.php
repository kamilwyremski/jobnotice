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

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if(isset($_GET['action']) and $_GET['action']=='classifieds_sugested_keywords' and !empty($_GET['keywords'])){
	echo(json_encode(classified::listNames($_GET['keywords'])));
	die();
}

if(_CLASSIFIEDS_PATH_ and (isset($_GET['state']) or isset($_GET['category']) or isset($_GET['type']))){
	$redirect_path = '';
	if(isset($_GET['state'])){
		if($_GET['state']){$redirect_path .= '/'._PREFIX_STATE_.$_GET['state'];}
		unset($_GET['state']);
		if(isset($_GET['state2'])){
			if($_GET['state2']){$redirect_path .= '/'.$_GET['state2'];}
			unset($_GET['state2']);
		}
	}
	if(isset($_GET['category'])){
		if($_GET['category']>0){
			$category_data = category::show($_GET['category']);
			if($category_data){
				$redirect_path .= '/'._PREFIX_CATEGORY_.$category_data['path'];
			}else{
				throw new noFoundException();
			}
		}
		unset($_GET['category']);
	}
	if(isset($_GET['type'])){
		if($_GET['type']){$redirect_path .= '/'._PREFIX_TYPE_.$_GET['type'];}
		unset($_GET['type']);
	}
	unset($_GET['path'],$_GET['slug'],$_GET['id']);
	$get = array_filter($_GET);
	if(!empty($get) and (sizeof($get)>1 or !isset($get['page']))){
		$get['search'] = '';
	}
	$http_query = http_build_query($get);
	if($http_query && $http_query!='search='){$redirect_path .= '?'.$http_query;}
	if(substr($redirect_path, 0, 1)=='?' or !$redirect_path){
		$redirect_path = path('classifieds').$redirect_path;
	}else{
		$redirect_path = $settings['base_url'].$redirect_path;
	}
	header("HTTP/1.1 301 Moved Permanently");
	header('Location: '.$redirect_path);
	die('redirect');
}


if(!empty($_GET['path'])){
	$path_parts = array_filter(explode('/', $_GET['path']));
	if(substr($path_parts[0], 0, strlen(_PREFIX_STATE_))==_PREFIX_STATE_){
		$_GET['state'] = substr(array_shift($path_parts), strlen(_PREFIX_STATE_));

		if(isset($path_parts[0]) and substr($path_parts[0], 0, strlen(_PREFIX_CATEGORY_))!=_PREFIX_CATEGORY_ and substr($path_parts[0], 0, strlen(_PREFIX_TYPE_))!=_PREFIX_TYPE_){
			$_GET['state2'] = array_shift($path_parts);
		}
	}

	if(isset($path_parts[0]) and substr($path_parts[0], 0, strlen(_PREFIX_CATEGORY_))==_PREFIX_CATEGORY_){
		$_GET['category_path'] = substr(array_shift($path_parts), strlen(_PREFIX_CATEGORY_));
		foreach($path_parts as $category_path){
			if(substr($category_path, 0, strlen(_PREFIX_TYPE_))!=_PREFIX_TYPE_){
				$_GET['category_path'] .= '/'.array_shift($path_parts);
			}
		}
	}

	if(isset($path_parts[0]) and substr($path_parts[0], 0, strlen(_PREFIX_TYPE_))==_PREFIX_TYPE_){
		$_GET['type'] = substr(array_shift($path_parts), strlen(_PREFIX_TYPE_));
	}
}

if(!empty($_GET['path']) and $_GET['path']!=$links['classifieds'] and !isset($_GET['state']) and !isset($_GET['category_path']) and !isset($_GET['type'])){
	throw new noFoundException();
}

if(!empty($_GET['category_path'])){
	$category = category::showFromPath($_GET['category_path']);
	if($category){
		$_GET['category'] = $category['id'];
	}else{
		throw new noFoundException();
	}
}

if(isset($_GET['category']) and $_GET['category']>0){
	$categories = category::list($_GET['category']);
	if(empty($category)){
		$category = category::show($_GET['category']);
	}
}else{
	$categories = category::list();
}

if(!empty($_GET['state'])){
	$state = state::showBySlug($_GET['state']);
	if(empty($state)){
		throw new noFoundException();
	}
	if(!empty($_GET['state2'])){
		$state2 = state::showBySlug($_GET['state2'],$state['id']);
		if(empty($state2)){
			throw new noFoundException();
		}
	}
}

if(!empty($_GET['type'])){
	$type = type::showBySlug($_GET['type']);
	if(empty($type)){
		throw new noFoundException();
	}
}

if($settings['show_breadcrumbs']){
	$breadcrumbs = [];
	$path = path('classifieds').'/?';

	if(!empty($state)){
		$path .= '&state='.$state['slug'];
		array_unshift($breadcrumbs , ['path'=>$path, 'name'=>$state['name']]);
		if(!empty($state2)){
			$path .= '&state2='.$state2['slug'];
			array_push($breadcrumbs , ['path'=>$path, 'name'=>$state2['name']]);
		}
	}

	$path_temp = $path;
	if(!empty($category)){
		foreach(category::getBreadcrumbs($category) as $key=>$item){
			$path = $path_temp.'&category='.$item['id'];
			array_push($breadcrumbs , ['path'=>$path, 'name'=>$item['name']]);
		}
	}

	if(!empty($type)){array_push($breadcrumbs , ['path'=>$path.'&type='.$type['slug'], 'name'=>$type['name']]);}

	if(isset($_GET['search'])){array_push($breadcrumbs , ['path'=>$_SERVER['REQUEST_URI'], 'name'=>trans('Search results')]);}

	$render_variables['breadcrumbs'] = $breadcrumbs;
}

$settings['h1'] = $settings['seo_title'] = trans('Classifieds');

if(!empty($state)){
  $settings['h1'] = $state['name'];
  $settings['seo_title'] = $state['name'];
  if(!empty($state2)){
		$settings['h1'] = $state2['name'].' '.$settings['h1'];
		$settings['seo_title'] = $state2['name'].' '.$settings['seo_title'];
  }
}
if(!empty($type)){
  if($settings['h1']){
		$settings['h1'] .= ' '.makeFirstLetterSmall($type['name']);
  }else{
		$settings['h1'] .= $type['name'];
  }
  if($settings['seo_title']){
		$settings['seo_title'] .= ' '.makeFirstLetterSmall($type['name']);
  }else{
		$settings['seo_title'] .= $type['name'];
  }
}

if(!empty($category)){
	if(empty($categories)){
		$categories = category::list($category['category_id']);
	}
	$render_variables['category'] = $category;

	if($category['h1']){
		$settings['h1'] = $category['h1'];
	}else{
		if(!empty($state)){
			$settings['h1'] = $category['name'].' '.$settings['h1'];
		}else{
			$settings['h1'] = $category['name'].' '.makeFirstLetterSmall($settings['h1']);
		}
	}
	if($category['title']){
		$settings['seo_title'] = $category['title'];
	}else{
		if(!empty($state)){
			$settings['seo_title'] = $category['name'].' '.$settings['seo_title'];
		}else{
			$settings['seo_title'] = $category['name'].' '.makeFirstLetterSmall($settings['seo_title']);
		}
	}
	if($category['description']){
		$settings['seo_description'] = $category['description'];
	}
	if($category['keywords']){
		$settings['keywords'] = $category['keywords'];
	}
	if($category['content']){
		$settings['content'] = $category['content'];
	}
}

if(isset($_GET['search'])){
	if(!empty($_GET['username'])){
		$settings['seo_title'] = strip_tags($_GET['username']).' - '.$settings['seo_title'];
		$settings['h1'] = $settings['h1'].': '.strip_tags($_GET['username']);
		$settings['seo_description'] = strip_tags($_GET['username']).' - '.$settings['description'];
	}else{
		$settings['seo_title'] = trans('Search').' - '.$settings['seo_title'];
		$settings['h1'] = trans('Search').': '.$settings['h1'];
		$settings['seo_description'] = trans('Classifieds - search results').' - '.$settings['description'];
	}
}

if(empty($category['title'])){
	$settings['seo_title'] = $settings['seo_title'].' - '.$settings['title'];
}

$render_variables['classifieds'] = classified::list($settings['limit_page'],$_GET,'classifieds');

if($settings['google_maps']){
	foreach($render_variables['classifieds'] as $row){
		if($row['address_lat']!=0 or $row['address_long']!=0){
			$render_variables['classifieds_show_map'] = true;
		}
	}
}

if(!empty($categories)){

	$path = path('classifieds').'/?';

	if(!empty($state)){$path .= '&state='.$state['slug'];}

	if(!empty($state2)){$path .= '&state2='.$state2['slug'];}

	if(!empty($type)){$path .= '&type='.$type['slug'];}

	if(isset($_GET['search'])){
		$gets_array = $_GET;
		unset($gets_array['path'],$gets_array['state'],$gets_array['state2'],$gets_array['category'],$gets_array['type'],$gets_array['page'],$gets_array['sort'],$gets_array['category_path'],$gets_array['slug']);
		$path .= '&'.http_build_query($gets_array);
	}

	$path_temp = $path;
	foreach($categories as $key=>$item){
		$categories[$key]['search_path'] = $path.'&category='.$item['id'];
	}

	$render_variables['categories'] = $categories;

	if(isset($category)){
		$overCategory = category::show($category['category_id']);
		if($overCategory){
			$overCategory['search_path'] = $path_temp.'&category='.$overCategory['id'];
			$render_variables['overCategory'] = $overCategory;
		}
	}
}

if($settings['search_box_state']){
	$render_variables['states'] = state::listAll();
}
if($settings['search_box_type']){
	$render_variables['types'] = type::list();
}
if(isset($category)){
	$category_id = $category['id'];
}else{
	$category_id = 0;
}
if($settings['search_box_options']){
	$render_variables['options'] = option::list($category_id,'search');
}
if(isset($_GET['search']) and !empty($_GET['keywords'])){
	logsSearch::add($_GET['keywords']);
}
