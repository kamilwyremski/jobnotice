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

class settings
{

	public static function save(string $name, string $type = 'str')
	{
		global $db;
		static::checkAndAdd($name);
		$sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name=:name LIMIT 1');
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		if ($type == 'isset') {
			$sth->bindValue(':value', isset($_POST[$name]), PDO::PARAM_INT);
		} else {
			$sth->bindValue(':value', $_POST[$name], PDO::PARAM_STR);
		}
		$sth->execute();
	}

	public static function saveArrays(array $array_str = [], array $array_isset = [])
	{
		global $db;
		$sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name=:name LIMIT 1');
		foreach ($array_str as $name) {
			static::checkAndAdd($name);
			$sth->bindValue(':name', $name, PDO::PARAM_STR);
			$sth->bindValue(':value', $_POST[$name], PDO::PARAM_STR);
			$sth->execute();
		}
		foreach ($array_isset as $name) {
			static::checkAndAdd($name);
			$sth->bindValue(':name', $name, PDO::PARAM_STR);
			$sth->bindValue(':value', isset($_POST[$name]), PDO::PARAM_INT);
			$sth->execute();
		}
	}

	public static function checkAndAdd(string $name)
	{
		global $db, $settings;
		if (!empty($settings) and !isset($settings[$name])) {
			$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'settings`(`name`) VALUES (:name)');
			$sth->bindValue(':name', $name, PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function addEmailToBlackList(string $email)
	{
		global $db, $settings;
		if ($email) {
			$black_list_email = array_map('trim', array_filter(explode(PHP_EOL, $settings['black_list_email'])));
			array_push($black_list_email, $email);
			asort($black_list_email);
			$black_list_email = implode(array_unique($black_list_email), PHP_EOL);
			$sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name="black_list_email" limit 1');
			$sth->bindValue(':value', $black_list_email, PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function addIpToBlackList(string $ip)
	{
		global $db, $settings;
		if ($ip) {
			$black_list_ip = array_map('trim', array_filter(explode(PHP_EOL, $settings['black_list_ip'])));
			array_push($black_list_ip, $ip);
			asort($black_list_ip);
			$black_list_ip = implode(array_unique($black_list_ip), PHP_EOL);
			$sth = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'settings` SET value=:value WHERE name="black_list_ip" limit 1');
			$sth->bindValue(':value', $black_list_ip, PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function checkWordsBlackList(string $content)
	{
		global $settings;
		$filtered_text = str_replace("&nbsp;", " ", $content);
		if ($settings['black_list_words']) {
			$filter_terms = array_map('trim', array_filter(explode(',', $settings['black_list_words'])));
			foreach ($filter_terms as $word) {
				$match_count = preg_match_all('/\b' . $word . '(?=[.,]|$|\s)/', $content, $matches);
				for ($i = 0; $i < $match_count; $i++) {
					$bwstr = trim($matches[0][$i]);
					$filtered_text = preg_replace('/\b' . $bwstr . '(?=[.,]|$|\s)/', str_repeat("*", strlen($bwstr)), $filtered_text);
				}
			}
		}
		return $filtered_text;
	}

	public static function checkEmailBlackList(string $email)
	{
		global $settings;
		if ($email and $settings['black_list_email'] and in_array($email, explode(PHP_EOL, $settings['black_list_email']))) {
			return true;
		} else {
			return false;
		}
	}

	public static function checkIpBlackList(string $ip)
	{
		global $settings;
		if ($ip and $settings['black_list_ip'] and in_array($ip, explode(PHP_EOL, $settings['black_list_ip']))) {
			return true;
		} else {
			return false;
		}
	}

	public static function checkCaptcha(array $data)
	{
		global $settings;
		if ($settings['recaptcha_site_key'] and $settings['recaptcha_secret_key']) {
			$verify = curl_init();
			curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
			curl_setopt($verify, CURLOPT_POST, true);
			curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
				'secret' => $settings['recaptcha_secret_key'],
				'response' => $data['recaptcha_response']
			]));
			curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
			$response = json_decode(curl_exec($verify), true);
			if ($response['success'] == true) {
				return true;
			} else {
				return false;
			}
		} else {
			if ($data['captcha'] == $_SESSION['captcha']) {
				return true;
			} else {
				return false;
			}
		}
	}

	public static function randomPassword(int $length = 8)
	{
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = [];
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < $length; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);
	}
}
