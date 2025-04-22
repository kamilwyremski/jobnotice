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

class logsSearch
{

	public static function add(string $text)
	{
		global $db, $user;
		$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'logs_search`(`text`, `user_id`, `ip`) VALUES (:text,:user_id,:ip)');
		$sth->bindValue(':text', strip_tags($text), PDO::PARAM_STR);
		$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
		$sth->bindValue(':ip', getClientIP(), PDO::PARAM_STR);
		$sth->execute();
	}

	public static function list(int $limit = 100)
	{
		global $db;
		$logs_searchs = [];
		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS ls.*, u.username, u.email FROM ' . _DB_PREFIX_ . 'logs_search ls LEFT JOIN ' . _DB_PREFIX_ . 'user u ON ls.user_id = u.id ORDER BY ' . orderBy() . ' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$logs_searchs[] = $row;
		}
		generatePagination($limit);
		return $logs_searchs;
	}

	public static function removeAll()
	{
		global $db;
		$db->query('TRUNCATE `' . _DB_PREFIX_ . 'logs_search`');
	}
}
