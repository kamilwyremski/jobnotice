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

class user {

	public static $types = ['Worker','Employer'];

	public function __construct () {
		global $db, $settings;
		$this->logged_in = false;

		if(isset($_GET['log_out']) and !empty($_GET['token']) and checkToken('logout',$_GET['token'])){
			$this->logOut();
			header("Location: ".$settings['base_url']);
			die('redirect');
		}elseif(!empty($_SESSION['user']['id']) and !empty($_SESSION['user']['session_code'])){
			$this->loginFromSession();
		}elseif(!empty($_COOKIE['user_id']) and !empty($_COOKIE['user_code'])){
			$_SESSION['user']['id'] = $_COOKIE['user_id'];
			$_SESSION['user']['session_code'] = $_COOKIE['user_code'];
			$this->loginFromSession();
		}
	}

	public function __get($value){
		if(isset($this->user_data[$value])){
			return $this->user_data[$value];
		}
		return false;
	}

	public function loginFromSession(){
		global $db;
		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE user_id=:user_id AND code=:code LIMIT 1');
		$sth->bindValue(':user_id', $_SESSION['user']['id'], PDO::PARAM_INT);
		$sth->bindValue(':code', $_SESSION['user']['session_code'], PDO::PARAM_STR);
		$sth->execute();

		if($sth->fetchColumn()){
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
			$sth->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT);
			$sth->execute();
			$user = $sth->fetch(PDO::FETCH_ASSOC);
			if($user!=''){
				$this->user_data = $user;
				$this->logged_in = true;
			}
		}else{
			$this->logOut();
		}
	}

	public function login(array $data){
		global $db, $settings;
		if($settings['check_ip_user']){
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE code=:code AND ip=:ip LIMIT 1');
			$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		}else{
			$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'session_user WHERE code=:code LIMIT 1');
		}
		$sth->bindValue(':code', $data['session_code'], PDO::PARAM_STR);
		$sth->execute();
		if($sth->fetchColumn()){

			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE (username=:username OR email=:username) LIMIT 1');
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->execute();

			$user = $sth->fetch(PDO::FETCH_ASSOC);

			if($user and $this->checkPassword($data['password'], $user['password'])){
				if($user['active']=='1'){
					if($user['username']==''){
						header("Location: ".path('login')."?complete_data=".$user['activation_code']);
						die('redirect');
					}

					static::setSession($user['id'],$data['session_code']);

					logsUser::add($user['id']);

					if(!empty($_GET['redirect'])){
						header("Location: ".$settings['base_url'].'/'.$_GET['redirect']);
					}else{
						header("Location: ".$settings['base_url']);
					}
					die('redirect');
				}else{
					static::removeSessionCode($data['session_code']);
					throw new Exception(trans('The account has not been activated yet.'));
				}
			}else{
				static::removeSessionCode($data['session_code']);
				throw new Exception(trans('The data provided is incorrect'));
			}
		}else{
			throw new Exception(trans('Session error'));
		}
	}

	public static function checkCodeAndActivate(string $activation_code){
		global $db;
		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE active=0 AND activation_code=:activation_code LIMIT 1');
		$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
		$sth->execute();
		$id = $sth->fetch(PDO::FETCH_ASSOC)['id'];
		if($id){
			static::activate($id);
			return true;
		}else{
			return false;
		}
	}

	public static function getIdFromEmail(string $email){
		global $db;
		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
		$sth->bindValue(':email', $email, PDO::PARAM_STR);
		$sth->execute();
		$id = $sth->fetch(PDO::FETCH_ASSOC)['id'];
		return $id ? $id : 0;
	}

	public static function activate(int $id){
		global $db;
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET active=1, activation_ip=:activation_ip, activation_date=NOW() WHERE id=:id LIMIT 1');
		$sth->bindValue(':activation_ip', getClientIp(), PDO::PARAM_STR);
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function setModerator(int $id){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET moderator=1 WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function unSetModerator(int $id){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET moderator=0 WHERE id=:id limit 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function newSessionCode(){
		global $db;
		$session_code = bin2hex(random_bytes(32));
		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`code`, `ip`) VALUES (:code,:ip)');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();
		return $session_code;
	}

	public static function removeSessionCode(string $session_code){
		global $db;
		$sth = $db->prepare('DELETE FROM `'._DB_PREFIX_.'session_user` WHERE code=:code');
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->execute();
	}

	public function logOut(){
		global $db;
		$this->logged_in = false;
		if(!empty($_SESSION['user']['session_code'])){
			$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'session_user WHERE code=:code');
			$sth->bindValue(':code', $_SESSION['user']['session_code'], PDO::PARAM_STR);
			$sth->execute();
		}
		unset($_SESSION['user']);
		unset($_SESSION['token']);
		setcookie("user_id", "", time() - 3600);
		setcookie("user_code", "", time() - 3600);
	}

	public function register(array $data){
		global $db;

		if(!settings::checkCaptcha($data)){
			$error['captcha'] = trans('Invalid captcha code. Show thay you are not robot!');
		}else{
			if(settings::checkEmailBlackList($data['email']) or settings::checkIpBlackList(getClientIp())){
				$error['info'] = trans('The account could not be submitted');
			}else{
				if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL) or strlen($data['email'])>64) {
					$error['email'] = trans('Incorrect e-mail address');
				}else{
					$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
					$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
					$sth->execute();
					if($sth->fetchColumn()){
						$error['email'] = trans('E-mail already exists in the database.');
					}
				}
				$old_username = $data['username'];
				$data['username'] = slugWithUpper(strip_tags($data['username']));
				if(!$data['username'] or strlen($data['username'])>64 or $old_username!=$data['username']){
					$error['username'] = trans('Invalid username');
				}else{
					$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
					$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
					$sth->execute();
					if($sth->fetchColumn()){
						$error['username'] = trans('The selected username is already taken');
					}
				}
				if(!$data['password'] or strlen($data['password'])>32){
					$error['password'] = trans('The password is incorrect');
				}elseif($data['password']!=$data['password_repeat']){
					$error['password'] = trans('Entered passwords are different');
				}
				if(!isset($data['rules'])){
					$error['rules'] = trans('This field is mandatory');
				}
				if(!in_array($data['type'],static::$types)){
					$error['type'] = trans('Select account type');
				}
				if($data['type']=='Employer' and (empty($data['name']) or empty($data['nip']) or empty($data['address']))){
					$error['account_employer'] = trans('Fill out all company details');
				}
			}
		}

		if(isset($error)){
			return ['status'=>false,'error'=>$error];
		}else{

			$activation_code = bin2hex(random_bytes(32));
			if(!isset($data['name'])){
				$data['name'] = '';
			}
			if(!isset($data['nip'])){
				$data['nip'] = '';
			}
			if(!isset($data['address'])){
				$data['address'] = '';
			}

			mail::send('register',$data['email'],['activation_code'=>$activation_code, 'password'=>$data['password'], 'username'=>$data['username'], 'email'=>$data['email']]);

			$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'user`(`type`, `username`, `email`, `password`, `name`, `nip`, `address`, `activation_code`, `register_ip`) VALUES (:type,:username,:email,:password,:name,:nip,:address,:activation_code,:register_ip)');
			$sth->bindValue(':type', $data['type'], PDO::PARAM_STR);
			$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
			$sth->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
			$sth->bindValue(':name', strip_tags($data['name']), PDO::PARAM_STR);
			$sth->bindValue(':nip', strip_tags($data['nip']), PDO::PARAM_STR);
			$sth->bindValue(':address', strip_tags($data['address']), PDO::PARAM_STR);
			$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
			$sth->bindValue(':register_ip', getClientIp(), PDO::PARAM_STR);
			$sth->execute();

			return ['status'=>true];
		}
	}

	public function resetPassword(array $data){
		global $db;

		if(!settings::checkCaptcha($data)){
			throw new Exception(trans('Invalid captcha code. Show thay you are not robot!'));
		}

		$sth = $db->prepare('SELECT id, email, username FROM '._DB_PREFIX_.'user WHERE (username=:username OR email=:username) LIMIT 1');
		$sth->bindValue(':username', strip_tags($data['username']), PDO::PARAM_STR);
		$sth->execute();
		$user_data = $sth->fetch(PDO::FETCH_ASSOC);
		if($user_data==''){
			throw new Exception(trans('The data provided is incorrect'));
		}
		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'reset_password WHERE active=1 AND date>(NOW() - INTERVAL 1 DAY) AND user_id=:user_id LIMIT 1');
		$sth->bindValue(':user_id', $user_data['id'], PDO::PARAM_INT);
		$sth->execute();
		if($sth->fetchColumn()){
			throw new Exception(trans('Link to change your password has been sent'));
		}

		$code = bin2hex(random_bytes(32));

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'reset_password`(`user_id`, `active`, `code`) VALUES (:user_id,1,:code)');
		$sth->bindValue(':user_id', $user_data['id'], PDO::PARAM_INT);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		mail::send('reset_password',$user_data['email'], ['reset_password_code'=>$code, 'username'=>$user_data['username']]);
	}

	public function resetPasswordNew(string $code){
		global $db;
		$sth = $db->prepare('SELECT user_id FROM '._DB_PREFIX_.'reset_password WHERE active=1 AND date>(NOW() - INTERVAL 1 DAY) AND code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		$user_id = $sth->fetch(PDO::FETCH_ASSOC);
		return $user_id ? $user_id : 0;
	}

	public function resetPasswordNewCheck(int $user_id,array $data,string $code){
		global $db;

		if($data['password']!=$data['password_repeat']){
			throw new Exception(trans('Entered passwords are different'));
		}elseif($data['password']=='' or strlen($data['password'])>32){
			throw new Exception(trans('The password is incorrect'));
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'reset_password SET used=1, active=0 WHERE code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET password=:password WHERE id=:id LIMIT 1');
		$sth->bindValue(':password', $this->createPassword($data['password']), PDO::PARAM_STR);
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
	}

	public function createPassword(string $password){
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public function checkPassword(string $password, string $hash){
		return password_verify($password, $hash);
	}

	public function checkCompleteData(string $code){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE username="" AND active=1 AND activation_code=:code LIMIT 1');
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public function completeData(string $code,array $data){
		global $db;
		if(!isset($data['rules'])){
			throw new Exception(trans('You must approve the rules and privacy policy'));
		}

		if(!in_array($data['type'],static::$types)){
			throw new Exception(trans('Select account type'));
		}
		if($data['type']=='Employer' and (empty($data['name']) or empty($data['nip']) or empty($data['address']))){
			throw new Exception(trans('Fill out all company details'));
		}

		$old_username = $data['username'];
		$data['username'] = slugWithUpper(strip_tags($data['username']));
		if(!$data['username'] or strlen($data['username'])>64 or $old_username!=$data['username']){
			throw new Exception(trans('Invalid username'));
		}

		$sth = $db->prepare('SELECT 1 FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->execute();
		if($sth->fetchColumn()){
			throw new Exception(trans('The selected username is already taken'));
		}

		if(!isset($data['name'])){
			$data['name'] = '';
		}
		if(!isset($data['nip'])){
			$data['nip'] = '';
		}
		if(!isset($data['address'])){
			$data['address'] = '';
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET username=:username, type=:type, name=:name, nip=:nip, address=:address WHERE active=1 AND activation_code=:code AND username="" LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->bindValue(':type', $data['type'], PDO::PARAM_STR);
		$sth->bindValue(':name', strip_tags($data['name']), PDO::PARAM_STR);
		$sth->bindValue(':nip', strip_tags($data['nip']), PDO::PARAM_STR);
		$sth->bindValue(':address', strip_tags($data['address']), PDO::PARAM_STR);
		$sth->bindValue(':code', $code, PDO::PARAM_STR);
		$sth->execute();

		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE username=:username LIMIT 1');
		$sth->bindValue(':username', $data['username'], PDO::PARAM_STR);
		$sth->execute();
		$user = $sth->fetch(PDO::FETCH_ASSOC);

		static::setSession($user['id']);

		logsUser::add($user['id']);

	}

	public static function setSession(int $user_id, string $session_code = ''){
		global $db;

		if(!$session_code){
			$session_code = bin2hex(random_bytes(32));
		}

		$_SESSION['user']['id'] = $user_id;
		$_SESSION['user']['session_code'] = $session_code;

		$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'session_user`(`user_id`, `code`, `ip`) VALUES (:user_id,:code,:ip)');
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
		$sth->bindValue(':code', $session_code, PDO::PARAM_STR);
		$sth->bindValue(':ip', getClientIp(), PDO::PARAM_STR);
		$sth->execute();

		setcookie('user_id', $user_id, time() + (86400 * 30), "/");
		setcookie('user_code', $session_code, time() + (86400 * 30), "/");
	}

	public function getAllData(){
		global $db;

		$sth = $db->prepare('SELECT (SELECT COUNT(1) FROM '._DB_PREFIX_.'classified WHERE user_id=:user_id) AS number_classifieds, (SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_user WHERE user_id=:user_id) AS number_login, (SELECT date FROM '._DB_PREFIX_.'logs_user WHERE user_id=:user_id order by date desc LIMIT 1,1) AS last_login, (SELECT date FROM '._DB_PREFIX_.'reset_password WHERE user_id=:user_id AND used=1 order by date desc LIMIT 1) AS last_reset_password');
		$sth->bindValue(':user_id', $this->id, PDO::PARAM_INT);
		$sth->execute();

		foreach($sth->fetch(PDO::FETCH_ASSOC) as $key=>$value){
			$this->user_data[$key] = $value;
		}
	}

	public static function getUsernameFromId(int $user_id){
		global $db;
		$sth = $db->prepare('SELECT username FROM '._DB_PREFIX_.'user WHERE id=:user_id LIMIT 1');
		$sth->bindValue(':user_id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		$username = $sth->fetch(PDO::FETCH_ASSOC)['username'];
		return $username;
	}

	public function changePassword(array $data){
		global $db;
		if($data['new_password']==$data['repeat_new_password']){
			if($this->checkPassword($data['old_password'], $this->password)){
				$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET password=:password WHERE id=:id LIMIT 1');
				$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
				$sth->bindValue(':password', $this->createPassword($data['new_password']), PDO::PARAM_STR);
				$sth->execute();
			}else{
				throw new Exception(trans('Enter proper old password'));
			}
		}else{
			throw new Exception(trans('Entered passwords are different'));
		}
	}

	public function saveDescription(string $description){
		global $db, $purifier;
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET description=:description WHERE id=:id LIMIT 1');
		$sth->bindValue(':description', settings::checkWordsBlackList(nofollow($purifier->purify(trim($description)))), PDO::PARAM_STR);
		$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['description'] = $description;
	}

	public function saveUserData(array $data){
		global $db;
		if(!isset($data['state_id'])){$data['state_id']=0;}
		if(!isset($data['state2_id'])){$data['state2_id']=0;}
		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET address=:address, phone=:phone, facebook_url=:facebook_url, state_id=:state_id, state2_id=:state2_id WHERE id=:id LIMIT 1');
		$sth->bindValue(':address', trim(strip_tags($data['address'])), PDO::PARAM_STR);
		$sth->bindValue(':phone', trim(strip_tags($data['phone'])), PDO::PARAM_STR);
		$sth->bindValue(':facebook_url', trim(strip_tags($data['facebook_url'])), PDO::PARAM_STR);
		$sth->bindValue(':state_id', $data['state_id'], PDO::PARAM_INT);
		$sth->bindValue(':state2_id', $data['state2_id'], PDO::PARAM_INT);
		$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
		$sth->execute();
		$this->user_data['address'] = $data['address'];
		$this->user_data['phone'] = $data['phone'];
		$this->user_data['facebook_url'] = $data['facebook_url'];
		$this->user_data['state_id'] = $data['state_id'];
		$this->user_data['state2_id'] = $data['state2_id'];
	}

	public function getId(){
		if($this->logged_in){
			return $this->id;
		}
		return 0;
	}

	public function loginFB(){
		global $settings;
		$fb_email = '';
		if(!empty($_REQUEST['code'])){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/oauth/access_token?fields=email,name&client_id=".$settings['facebook_api']."&redirect_uri=".urlencode(absolutePath('login').'?facebook_login')."&client_secret=".$settings['facebook_secret']."&code=".$_REQUEST['code']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$fb_params = json_decode(curl_exec($ch));
			curl_close($ch);
			if(isset($fb_params->access_token)){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/me?fields=email,name&access_token=".$fb_params->access_token);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$fb_user = json_decode(curl_exec($ch), true);
				if(isset($fb_user['email'])){
					$fb_email = $fb_user['email'];
				}
				curl_close($ch);
			}
		}
		if($fb_email){
			$this->loginByEmail($fb_email,'fb');
		}
	}

	public function loginGoogle(){
		global $settings;
		$google_email = '';
		if(!empty($_REQUEST['code'])){
			$url = 'https://accounts.google.com/o/oauth2/token';
			$curlPost = 'client_id='.$settings['google_id'].'&redirect_uri='.urlencode(absolutePath('login')).'&client_secret='.$settings['google_secret'].'&code='.$_REQUEST['code'].'&grant_type=authorization_code';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			$data = json_decode(curl_exec($ch), true);
			if(!empty($data['access_token'])){
				$url = 'https://www.googleapis.com/oauth2/v2/userinfo?fields=email,verified_email';	
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$data['access_token']]);
				$data2 = json_decode(curl_exec($ch), true);
				if(!empty($data2['email']) and !empty($data2['verified_email'])){
					$google_email = $data2['email'];
				}
			}
		}
		if($google_email){
			$this->loginByEmail($google_email,'google');
		}
	}

	public function loginByEmail(string $email,string $source=''){
		global $db, $settings;

		if(settings::checkEmailBlackList($email) or settings::checkIpBlackList(getClientIp())){
			$error['info'] = trans('The account could not be submitted');
		}else{
			$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE email=:email LIMIT 1');
			$sth->bindValue(':email', $email, PDO::PARAM_STR);
			$sth->execute();
			$user_data = $sth->fetch(PDO::FETCH_ASSOC);

			if($user_data){

				if($user_data['active']=='0'){
					$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'user` SET active=1 WHERE id=:id');
					$sth->bindValue(':id', $user_data['id'], PDO::PARAM_INT);
					$sth->execute();
				}
				if($user_data['username']==''){
					header("Location: ".path('login')."?complete_data=".$user_data['activation_code']);
					die('redirect');
				}

				static::setSession($user_data['id']);

				logsUser::add($user_data['id']);

				if(!empty($_GET['redirect'])){
					header("Location: ".$settings['base_url'].'/'.$_GET['redirect']);
				}else{
					header("Location: ".$settings['base_url']);
				}
				die('redirect');
			}else{

				$activation_code = bin2hex(random_bytes(32));
				$password = settings::randomPassword();
				$register_fb = $register_google = 0;

				if($source=='fb'){
					mail::send('register_fb',$email,['activation_code'=>$activation_code, 'password'=>$password, 'email'=>$email]);
					$register_fb = 1;
				}elseif($source=='google'){
					mail::send('register_google',$email,['activation_code'=>$activation_code, 'password'=>$password, 'email'=>$email]);
					$register_google = 1;
				}

				$sth = $db->prepare('INSERT INTO `'._DB_PREFIX_.'user`(`active`, `email`, `password`, `activation_code`, `register_fb`, `register_google`, `register_ip`, `activation_date`, `activation_ip`) VALUES (1, :email,:password,:activation_code,:register_fb,:register_google,:register_ip,NOW(), :activation_ip)');
				$sth->bindValue(':email', strip_tags($email), PDO::PARAM_STR);
				$sth->bindValue(':password', $this->createPassword($password), PDO::PARAM_STR);
				$sth->bindValue(':activation_code', $activation_code, PDO::PARAM_STR);
				$sth->bindValue(':register_fb', $register_fb, PDO::PARAM_INT);
				$sth->bindValue(':register_google', $register_google, PDO::PARAM_INT);
				$sth->bindValue(':register_ip', getClientIp(), PDO::PARAM_STR);
				$sth->bindValue(':activation_ip', getClientIp(), PDO::PARAM_STR);
				$sth->execute();

				header("Location: ".path('login')."?complete_data=".$activation_code);
				die('redirect');
			}
		}
	}

	public static function showProfile(string $username){
		global $db;
		$sth = $db->prepare('SELECT u.*,
		(SELECT count(1) FROM '._DB_PREFIX_.'classified WHERE user_id=u.id) AS number_classifieds,
		(SELECT count(1) FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id) AS number_login,
		(SELECT date FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id ORDER BY date DESC LIMIT 1,1) AS last_login
		FROM '._DB_PREFIX_.'user u WHERE u.active=1 AND u.username=:username LIMIT 1');
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->execute();
		$profile = $sth->fetch(PDO::FETCH_ASSOC);
		return ($profile);
	}

	public static function showData(int $id){
		global $db;
		$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	public function saveAvatar(){
		global $db;

		static::removeAvatar($this->getId());

		$path_parts = pathinfo($_FILES['avatar']['name']);
		$path_parts['extension'] = strtolower($path_parts['extension']);

		if(in_array($path_parts['extension'], ['jpg','jpeg','png'])){

			chmod(_FOLDER_AVATARS_, 0777);

			$url = slug($path_parts['filename']).'.png';
			$i = 0;
			while(file_exists(_FOLDER_AVATARS_.$url)) {
				$url = slug($path_parts['filename']).'_'.$i.'.png';
				$i++;
			}

			if($path_parts['extension']=="jpg" || $path_parts['extension']=="jpeg"){
				$src = imagecreatefromjpeg($_FILES['avatar']['tmp_name']);
			}else{
				$src = imagecreatefrompng($_FILES['avatar']['tmp_name']);
			}

			list($width,$height) = getimagesize($_FILES['avatar']['tmp_name']);

			if($height >= 80){
				$newheight = 80;
			}else{
				$newheight = $height;
			}
			$newwidth = round($newheight / $height * $width);

			$tmp=imagecreatetruecolor($newwidth,$newheight);
			if($path_parts['extension']=="png"){
				imagesavealpha($tmp, true);
				$color = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
				imagefill($tmp, 0, 0, $color);
			}
			imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight, $width,$height);

			imagepng($tmp,_FOLDER_AVATARS_.$url);
			imagedestroy($src);

			chmod(_FOLDER_AVATARS_, 0755);

			$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET avatar=:avatar WHERE id=:id LIMIT 1');
			$sth->bindValue(':avatar', $url, PDO::PARAM_STR);
			$sth->bindValue(':id', $this->id, PDO::PARAM_INT);
			$sth->execute();
			$this->user_data['avatar'] = $url;
		}
	}

	public static function removeAvatar(int $user_id){
		global $db, $user;

		$sth = $db->prepare('SELECT avatar FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
		$old_avatar = $sth->fetch(PDO::FETCH_ASSOC)['avatar'];

		if($old_avatar){
			chmod(_FOLDER_AVATARS_, 0777);
			unlink(_FOLDER_AVATARS_.$old_avatar);
			chmod(_FOLDER_AVATARS_, 0755);
		}

		$sth = $db->prepare('UPDATE '._DB_PREFIX_.'user SET avatar="" WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $user_id, PDO::PARAM_INT);
		$sth->execute();
	}

	public static function list(int $limit=100,array $data=[]){
		global $db;
		$users = [];
		$where_statement = ' true ';
		$bind_values = [];
		if(isset($data['search'])){
			if(!empty($data['username'])){
				$where_statement .= ' AND username LIKE :username ';
				$bind_values['username'] = '%'.$_GET['username'].'%';
			}
			if(!empty($data['email'])){
				$where_statement .= ' AND email LIKE :email ';
				$bind_values['email'] = '%'.$data['email'].'%';
			}
			if(!empty($data['active'])){
				if($data['active']=='yes'){
					$where_statement .= ' AND active="1" ';
				}elseif($data['active']=='no'){
					$where_statement .= ' AND active="0" ';
				}
			}
			if(!empty($data['moderator'])){
				if($data['moderator']=='yes'){
					$where_statement .= ' AND moderator="1" ';
				}elseif($data['moderator']=='no'){
					$where_statement .= ' AND moderator="0" ';
				}
			}
			if(!empty($_GET['register_fb'])){
				if($data['register_fb']=='yes'){
					$where_statement .= ' AND register_fb="1" ';
				}elseif($data['register_fb']=='no'){
					$where_statement .= ' AND register_fb="0" ';
				}
			}
			if(!empty($data['register_google'])){
				if($data['register_google']=='yes'){
					$where_statement .= ' AND register_google="1" ';
				}elseif($data['register_google']=='no'){
					$where_statement .= ' AND register_google="0" ';
				}
			}
			if(!empty($data['date_from'])){
				$where_statement .= ' AND date >= :date_from ';
				$bind_values['date_from'] = $data['date_from'];
			}
			if(!empty($data['date_to'])){
				$where_statement .= ' AND date <= :date_to ';
				$bind_values['date_to'] = $data['date_to'].' 23:59:59';
			}
			if(!empty($data['register_ip'])){
				$where_statement .= ' AND register_ip LIKE :register_ip ';
				$bind_values['register_ip'] = '%'.$data['register_ip'].'%';
			}
		}

		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS *,
			(SELECT COUNT(1) FROM '._DB_PREFIX_.'classified WHERE user_id=u.id) AS amount_classifieds,
			(SELECT COUNT(1) FROM '._DB_PREFIX_.'classified WHERE user_id=u.id AND active=1) AS amount_active_classifieds,
			(SELECT date FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id ORDER BY date desc LIMIT 1) AS last_login,
			(SELECT COUNT(1) FROM '._DB_PREFIX_.'logs_user WHERE user_id=u.id) AS amount_logins
			FROM '._DB_PREFIX_.'user u WHERE '.$where_statement.' ORDER BY '.orderBy().' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		foreach($bind_values as $key => $value){
			$sth->bindValue(':'.$key, $value, PDO::PARAM_STR);
		}
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$users[] = $row;
		}
		generatePagination($limit);
		return $users;
	}

	public static function listResetPasswordLogs(int $limit = 100){
		global $db;
		$reset_password = [];
		$sth = $db->prepare('SELECT SQL_CALC_FOUND_ROWS rp.*, u.username, u.email FROM '._DB_PREFIX_.'reset_password rp LEFT JOIN '._DB_PREFIX_.'user u ON rp.user_id = u.id ORDER BY '.orderBy().' LIMIT :limit_from, :limit_to');
		$sth->bindValue(':limit_from', paginationPageFrom($limit), PDO::PARAM_INT);
		$sth->bindValue(':limit_to', $limit, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			$reset_password[] = $row;
		}
		generatePagination($limit);
		return $reset_password;
	}

	public static function removeAllResetPasswordLogs(){
		global $db;
		$db->query('TRUNCATE `'._DB_PREFIX_.'reset_password`');
	}

	public static function remove(int $id){
		global $db;
		static::removeAvatar($id);
		$sth = $db->prepare('SELECT id FROM '._DB_PREFIX_.'classified WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		foreach($sth as $row){;
			classified::remove($row['id']);
		}
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'session_user WHERE user_id=:user_id');
		$sth->bindValue(':user_id', $id, PDO::PARAM_INT);
		$sth->execute();
		document::removeOfUser($id);
		$sth = $db->prepare('DELETE FROM '._DB_PREFIX_.'user WHERE id=:id LIMIT 1');
		$sth->bindValue(':id', $id, PDO::PARAM_INT);
		$sth->execute();
	}
}
