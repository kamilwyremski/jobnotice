<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2025 by IT Works Better https://itworksbetter.net
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

class logsUser
{

	public static function add(int $id)
	{
		global $db;
		$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'logs_user`(`user_id`, `ip`) VALUES (:user_id,:ip)');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
	}

	public static function list(int $limit = 100)
	{
		global $db;
		$logs_users = [];
		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS lu.*, u.username, u.email FROM ' . _DB_PREFIX_ . 'logs_user lu LEFT JOIN ' . _DB_PREFIX_ . 'user u ON lu.user_id = u.id ORDER BY ' . orderBy() . ' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$logs_users[] = $row;
		}
		generatePagination($limit);
		return $logs_users;
	}

	public static function removeWithoutUsers()
	{
		global $db;
		$db->query('DELETE FROM ' . _DB_PREFIX_ . 'logs_user WHERE user_id NOT IN (SELECT id FROM ' . _DB_PREFIX_ . 'user)');
	}

	public static function removeAll()
	{
		global $db;
		$db->query('TRUNCATE `' . _DB_PREFIX_ . 'logs_user`');
	}
}
