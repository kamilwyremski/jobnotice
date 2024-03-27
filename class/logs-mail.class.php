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

class logsMail
{

	public static function add(string $receiver, string $action = '', string $content = '', string $ip = '')
	{
		global $db;
		$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'logs_mail`(`receiver`, `action`, `content`, `ip`) VALUES (:receiver,:action,:content,:ip)');
		$sth->bindValue(':receiver', $receiver, PDO::PARAM_STR);
		$sth->bindValue(':action', $action, PDO::PARAM_STR);
		$sth->bindValue(':content', $content, PDO::PARAM_STR);
		$sth->bindValue(':ip', $ip, PDO::PARAM_STR);
		$sth->execute();
	}

	public static function list(int $limit = 100)
	{
		global $db;
		$logs_mails = [];
		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM ' . _DB_PREFIX_ . 'logs_mail ORDER BY ' . orderBy() . ' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$logs_mails[] = $row;
		}
		generatePagination($limit);
		return $logs_mails;
	}

	public static function removeAll()
	{
		global $db;
		$db->query('TRUNCATE `' . _DB_PREFIX_ . 'logs_mail`');
	}
}
