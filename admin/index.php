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

header('Content-Type: text/html; charset=utf-8');

session_start();

require_once('../vendor/autoload.php');
$loader = new \Twig\Loader\FilesystemLoader('views');
$twig = new \Twig\Environment($loader, [
    'cache' => 'tmp',
]);
$twig->addFilter(new \Twig\TwigFilter('trans', 'trans'));
$twig->addFilter(new \Twig\TwigFilter('showCurrency', 'showCurrency'));
$twig->addFunction(new \Twig\TwigFunction('path', 'absolutePath'));
$twig->addFunction(new \Twig\TwigFunction('generateToken', 'generateToken'));

require_once('../config/config.php');

$admin = new admin();

$title = $title_default = 'Admin Panel created by Kamil Wyremski';

$controller = 'index';
if($admin->is_logged()){
	if(isset($_GET['controller']) and isSlug($_GET['controller'])){
		if(file_exists('controller/'.$_GET['controller'].'.php')){
			$controller = $_GET['controller'];
			$title = ucfirst($controller).' - '.$title_default;
		}else{
			$controller = '404';
		}
	}
}else{
	$controller = 'login';
}

$render_variables = [];

class noFoundException extends Exception {}

try{
	include_once('controller/'.$controller.'.php');
}catch(noFoundException $e){
	include_once('controller/404.php');
}

echo $twig->render($controller.'.html', array_merge($render_variables, ['title' => $title, 'settings' => $settings, 'admin' => $admin->user_data, '_ADMIN_TEST_MODE_' => _ADMIN_TEST_MODE_, 'get' => $_GET, 'controller' => $controller, 'folder_admin' => basename(dirname($_SERVER['REQUEST_URI']))]));
