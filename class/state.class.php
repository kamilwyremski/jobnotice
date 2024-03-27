<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2024 by IT Works Better https://itworksbetter.net
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

class state
{

	public static function add(array $data = [], int $state_id = 0)
	{
		global $db;
		$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'state`(`state_id`, `position`, `slug`, `name`) VALUES (:state_id,:position,:slug,:name)');
		$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
		$sth->bindValue(':position', getPosition('state', ' state_id=' . $state_id . ' '), PDO::PARAM_INT);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
		$sth->execute();
	}

	public static function edit(int $id, array $data = [])
	{
		global $db;
		$sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'state` SET slug=:slug, name=:name WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->bindValue(':slug', slug($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
		$sth->execute();
	}

	public static function remove(int $id)
	{
		global $db;
		$sth = $db->prepare('DELETE FROM `' . _DB_PREFIX_ . 'state` WHERE state_id=:id');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		$sth = $db->prepare('DELETE FROM `' . _DB_PREFIX_ . 'state` WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function listAll()
	{
		global $db;
		$states = [];
		$sth = $db->query('SELECT * FROM ' . _DB_PREFIX_ . 'state ORDER BY state_id,position,name');
		foreach ($sth as $row) {
			if ($row['state_id']) {
				$states[$row['state_id']]['states'][$row['id']] = $row;
			} else {
				$states[$row['id']] = $row;
			}
		}
		return $states;
	}

	public static function list(int $state_id = 0)
	{
		global $db;
		$states = [];
		$sth = $db->prepare('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE state_id=:state_id ORDER BY position,name');
		$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
		$sth->execute();
		foreach ($sth as $row) {
			$states[$row['id']] = $row;
		}
		return $states;
	}

	public static function showById(int $id = 0)
	{
		global $db;
		$state = '';
		if ($id > 0) {
			$sth = $db->prepare('SELECT * from ' . _DB_PREFIX_ . 'state WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->execute();
			$state = $sth->fetch(PDO::FETCH_ASSOC);
		}
		return $state;
	}

	public static function showBySlug(string $slug, int $state_id = 0)
	{
		global $db;
		$sth = $db->prepare('SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE slug=:slug AND state_id=:state_id LIMIT 1');
		$sth->bindValue(':slug', $slug, PDO::PARAM_STR);
		$sth->bindValue(':state_id', $state_id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}
}
