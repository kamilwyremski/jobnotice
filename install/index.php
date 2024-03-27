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

session_start();

header('Content-Type: text/html; charset=utf-8');

ini_set("display_errors", "1");
error_reporting(E_ALL);
error_reporting(0);

ob_start();

if (phpversion() < 7.2) {
	die('Wrong version of PHP on the server. The minimum supported is 7.2');
}

if (!is_writable('../config/db.php')) {
	die('The file /config/db.php is not writable!');
}

$install = true;
require_once ('../config/db.php');

if (isset($mysql_server)) {
	header('location: ../admin');
	die('redirect...');
}

$settings['base_url'] = true;
require_once ('../php/global.php');

if (isset($_GET['lang']) and $_GET['lang'] != '') {
	$settings['lang'] = langLoad($_GET['lang']);
} else {
	$settings['lang'] = langLoad();
}
$langList = langList();

if (!empty($_POST['base_url']) and !empty($_POST['server']) and !empty($_POST['user']) and !empty($_POST['name']) and !empty($_POST['admin']) and !empty($_POST['password_admin']) and !empty($_POST['password_admin_repeat']) and !empty($_POST['email']) and isset($_POST['db_prefix'])) {

	if ($_POST['password_admin'] != $_POST['password_admin_repeat']) {
		$error = trans('Entered passwords are different');
	} else {

		define("_DB_PREFIX_", $_POST['db_prefix']);

		try {
			$db = new PDO('mysql:host=' . $_POST['server'] . ';dbname=' . $_POST['name'], $_POST['user'], $_POST['password'], [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
		} catch (PDOException $e) {
			$error = true;
		}

		if (isset($error)) {
			$error = trans('Error! Unable to connect to the server.');
		} else {

			$dir = '../config/db.php';
			if (!file_exists($dir)) {
				fwrite($dir, '');
			} else {
				chmod($dir, 0777);
			}

			file_put_contents('../config/db.php', '<?php
$mysql_server = \'' . $_POST['server'] . '\';
$mysql_user = \'' . $_POST['user'] . '\';
$mysql_pass = \'' . str_replace("'", "\'", $_POST['password']) . '\';
$mysql_db = \'' . $_POST['name'] . '\';

define("_DB_PREFIX_", "' . _DB_PREFIX_ . '");

');

			$sql = file_get_contents('jobnotice.sql');

			if (isset($_POST['sample_data'])) {
				$sql .= file_get_contents('jobnotice_sample_data.sql');
			}

			$sql = str_replace("CREATE TABLE IF NOT EXISTS `", "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_, $sql);
			$sql = str_replace("CREATE TABLE `", "CREATE TABLE `" . _DB_PREFIX_, $sql);
			$sql = str_replace("INSERT INTO `", "INSERT INTO `" . _DB_PREFIX_, $sql);
			$sql = str_replace("ALTER TABLE `", "ALTER TABLE `" . _DB_PREFIX_, $sql);
			$sql = str_replace("REFERENCES `", "REFERENCES `" . _DB_PREFIX_, $sql);

			$db->exec($sql);

			require_once ('../class/admin.class.php');
			$admin = new admin();
			$password_admin = $admin->createPassword($_POST['password_admin']);

			$sth = $db->prepare('SELECT 1 FROM ' . _DB_PREFIX_ . 'admin WHERE username=:username LIMIT 1');
			$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
			$sth->execute();
			if ($sth->fetchColumn()) {
				$sth = $db->prepare('UPDATE ' . _DB_PREFIX_ . 'admin SET password=:password WHERE username=:username LIMIT 1');
				$sth->bindValue(':password', $password_admin, PDO::PARAM_STR);
				$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
				$sth->execute();
			} else {
				$sth = $db->prepare('INSERT INTO ' . _DB_PREFIX_ . 'admin (`username`, `password`) VALUES (:username, :password)');
				$sth->bindValue(':username', $_POST['admin'], PDO::PARAM_STR);
				$sth->bindValue(':password', $password_admin, PDO::PARAM_STR);
				$sth->execute();
			}

			$sth = $db->prepare('UPDATE ' . _DB_PREFIX_ . 'settings SET value=:base_url WHERE name="base_url" LIMIT 1');
			$sth->bindValue(':base_url', webAddress($_POST['base_url']), PDO::PARAM_STR);
			$sth->execute();

			$template = 'default';
			if (!file_exists('../views/' . $template)) {
				$dirs = array_filter(glob('../views/*'), 'is_dir');
				$template = substr($dirs[0], 9);
			}

			$sth = $db->prepare('UPDATE ' . _DB_PREFIX_ . 'settings SET value=:template WHERE name="template" LIMIT 1');
			$sth->bindValue(':template', $template, PDO::PARAM_STR);
			$sth->execute();

			$sth = $db->prepare('UPDATE ' . _DB_PREFIX_ . 'settings SET value=:email WHERE name="email" LIMIT 1');
			$sth->bindValue(':email', $_POST['email'], PDO::PARAM_STR);
			$sth->execute();

			$sth = $db->prepare('UPDATE ' . _DB_PREFIX_ . 'settings SET value=:lang WHERE name="lang" LIMIT 1');
			$sth->bindValue(':lang', $settings['lang'], PDO::PARAM_STR);
			$sth->execute();

			chmod("../config/db.php", 0644);

			header('location: ' . $_POST['base_url'] . '');
			die('redirect...');
		}
	}
}
?>
<!doctype html>
<html lang="pl">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="Kamil Wyremski - wyremski.pl">
	<title>
		<?= trans('The installer script') ?>
	</title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>

<body>
	<div class="container">
		<a href="http://wyremski.pl" title="Creating websites"><img src="img/admin.png"
				alt="Admin Panel created by Kamil Wyremski" id="logo" /></a>
		<h5 class="text-center">
			<?= trans('Welcome to the installation program! Please fill in the fields below to pre-configure page.') ?>
		</h5>
		<?php
		if (isset($error)) {
			echo ('<h3 class="text-danger text-center">' . $error . '</h3>');
		}
		?>
		<br>
		<form method="get" class="form-horizontal">
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Select language') ?>:
				</label>
				<div class="col-sm-7">
					<select class="form-control" name="lang" title="<?= trans('Select language') ?>"
						onchange="this.form.submit()">
						<?php
						foreach ($langList as $key => $lang) {
							echo ('<option value="' . $lang . '"');
							if ($settings['lang'] == $lang) {
								echo (' selected ');
							}
							echo ('>' . $lang . '</option>');
						}
						?>
					</select>
				</div>
			</div>
		</form>
		<br>
		<form method="post" class="form">
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Base URL') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="base_url" placeholder="<?= trans('Base URL') ?>"
						value="<?php if (isset($_POST['base_url'])) {
							echo ($_POST['base_url']);
						} else {
							echo ('http://' . $_SERVER['HTTP_HOST']);
						} ?>"
						required title="<?= trans('Base URL') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('The database server') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="server" placeholder="<?= trans('The database server') ?>"
						value="<?php if (isset($_POST['server'])) {
							echo ($_POST['server']);
						} else {
							echo ('localhost');
						} ?>" required
						title="<?= trans('The database server') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('The database user name') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="user" placeholder="<?= trans('The database user name') ?>"
						value="<?php if (isset($_POST['user'])) {
							echo ($_POST['user']);
						} ?>" required
						title="<?= trans('The database user name') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('The database name') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="name" placeholder="<?= trans('The database name') ?>"
						value="<?php if (isset($_POST['name'])) {
							echo ($_POST['name']);
						} ?>" required
						title="<?= trans('The database name') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Password for database') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="password" name="password"
						placeholder="<?= trans('Password for database') ?>"
						value="<?php if (isset($_POST['password'])) {
							echo ($_POST['password']);
						} ?>"
						title="<?= trans('Password for database') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Username to Admin Panel') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="admin" placeholder="<?= trans('Username to Admin Panel') ?>"
						value="<?php if (isset($_POST['admin'])) {
							echo ($_POST['admin']);
						} else {
							echo ('administrator');
						} ?>" required
						title="<?= trans('Username to Admin Panel') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Password to Admin Panel') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="password" name="password_admin"
						placeholder="<?= trans('Password to Admin Panel') ?>"
						value="<?php if (isset($_POST['password_admin'])) {
							echo ($_POST['password_admin']);
						} ?>" required
						title="<?= trans('Password to Admin Panel') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Repeat password') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="password" name="password_admin_repeat"
						placeholder="<?= trans('Repeat password') ?>" required title="<?= trans('Repeat password') ?>" />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('E-mail Administrator') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="email" name="email" placeholder="<?= trans('E-mail Administrator') ?>"
						value="<?php if (isset($_POST['email'])) {
							echo ($_POST['email']);
						} ?>"
						title="<?= trans('E-mail Administrator') ?>" required />
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-5 col-form-label">
					<?= trans('Prefix tables in the database') ?>:
				</label>
				<div class="col-sm-7">
					<input class="form-control" type="text" name="db_prefix"
						placeholder="<?= trans('Prefix tables in the database') ?>"
						value="<?php if (isset($_POST['db_prefix'])) {
							echo ($_POST['db_prefix']);
						} ?>"
						title="<?= trans('Prefix tables in the database') ?>" pattern="[a-z_]*" />
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-7 offset-sm-5">
					<div class="checkbox">
						<label><input type="checkbox" name="sample_data" <?php if (isset($_POST['sample_data'])) {
							echo ('checked');
						} ?> />
							<?= trans('Install sample data (categories, states, etc.)') ?>
						</label>
					</div>
				</div>
			</div>
			<div class="form-group text-center">
				<input class="btn btn-primary" type="submit" value="<?= trans('Save') ?>" />
			</div>
		</form>
	</div>
	<footer>
		<p class="text-center small">Admin v5.1 - Project © 2019 by <a href="https://wyremski.pl" target="_blank"
				title="Web Design">Kamil Wyremski</a></p>
	</footer>
</body>

</html>