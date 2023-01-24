<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2023 by IT Works Better https://itworksbetter.net
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

class mail {

	public static function list(){
		global $db;
		$mails = [];
		$sth = $db->query('SELECT * FROM '._DB_PREFIX_.'mails order by name');
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {$mails[] = $row;}
		return $mails;
	}

	public static function save(array $mails){
		global $db;
		$sth = $db->prepare('UPDATE `'._DB_PREFIX_.'mails` SET subject=:subject, message=:message WHERE name=:name LIMIT 1');
		foreach($mails as $key=>$value){
			$sth->bindValue(':subject', $value['subject'], PDO::PARAM_STR);
			$sth->bindValue(':message', $value['message'], PDO::PARAM_STR);
			$sth->bindValue(':name', $key, PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function prepareMailing(array $data){
		global $db;
		if($data['type']=='newsletter'){
			$sth = $db->query('SELECT email, code FROM '._DB_PREFIX_.'newsletter WHERE active=1');
		}else	if($data['type']=='users'){
			$sth = $db->query('SELECT email, "" AS code FROM '._DB_PREFIX_.'user WHERE active=1');
		}else{
			return false;
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)){
			mailQueue::add('mailing',$row['email'],['subject'=>$data['subject'], 'message'=>$data['message'], 'newsletter_cancel_code'=>$row['code']]);
		}
	}

	public static function send(string $type,string $email,array $data=[]){
		global $db, $settings, $mail_smtp;
		$mail_sent = false;

		if($type!='' and $email!=''){

			if($settings['smtp'] and !isset($mail_smtp)){
				$mail_smtp = require_once(realpath(dirname(__FILE__)).'/../config/smtp.php');
			}

			if($type=='mailing' or $type=='test'){
				$mail_content = ['subject'=>$data['subject'],'message'=>$data['message']];
			}else{
				$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'mails WHERE name=:name limit 1');
				$sth->bindParam(':name', $type, PDO::PARAM_STR);
				$sth->execute();
				$mail_content = $sth->fetch(PDO::FETCH_ASSOC);
			}

			if($mail_content){

				$header = 'Reply-To: <'.$settings['email']."> \r\n";
				$message = '<!doctype html><html lang="'.$settings['lang'].'"><head><meta charset="utf-8">'.$mail_content['message'].'</head><body>';
				$subject = $mail_content['subject'];
				$ip = getClientIp();

				$message = str_replace("{title}",$settings['title'],$message);
				$subject = str_replace("{title}",$settings['title'],$subject);
				$message = str_replace("{base_url}",$settings['base_url'],$message);
				$subject = str_replace("{base_url}",$settings['base_url'],$subject);
				if($settings['logo']){
					$message = str_replace("{link_logo}",'<img src="'.makeAbsoluteUrl($settings['logo']).'" style="max-width:300px; max-height: 200px">',$message);
					$subject = str_replace("{link_logo}",'<img src="'.makeAbsoluteUrl($settings['logo']).'" style="max-width:300px; max-height: 200px">',$subject);
				}else{
					$message = str_replace("{link_logo}",'',$message);
					$subject = str_replace("{link_logo}",'',$subject);
				}
				$message = str_replace("{date}",date("Y-m-d"),$message);
				$subject = str_replace("{date}",date("Y-m-d"),$subject);
				if(isset($data['user_id']) and $data['user_id']>0){
					$data['username'] = user::getUsernameFromId($data['user_id']);
				}
				if(isset($data['username'])){
					$message = str_replace("{username}",$data['username'],$message);
					$subject = str_replace("{username}",$data['username'],$subject);
				}
				if(isset($data['activation_code'])){
					$message = str_replace("{activation_link}",absolutePath('login').'?activation_code='.$data['activation_code'],$message);
					$subject = str_replace("{activation_link}",absolutePath('login').'?activation_code='.$data['activation_code'],$subject);
				}
				if(isset($data['newsletter_activation_code'])){
					$message = str_replace("{newsletter_activation_link}",$settings['base_url'].'?newsletter_activation_code='.$data['newsletter_activation_code'],$message);
					$subject = str_replace("{newsletter_activation_link}",$settings['base_url'].'?newsletter_activation_code='.$data['newsletter_activation_code'],$subject);
				}
				if(!empty($data['newsletter_cancel_code'])){
					$message .= '<br><hr><br><p><a href="'.$settings['base_url'].'?newsletter_cancel='.$data['newsletter_cancel_code'].'">'.trans('Delete my email address from the newsletter').'</a></p>';
				}
				if(isset($data['password'])){
					$message = str_replace("{password}",$data['password'],$message);
					$subject = str_replace("{password}",$data['password'],$subject);
				}
				if(isset($data['reset_password_code'])){
					$message = str_replace("{reset_password_link}",absolutePath('login').'?new_password='.$data['reset_password_code'],$message);
					$subject = str_replace("{reset_password_link}",absolutePath('login').'?new_password='.$data['reset_password_code'],$subject);
				}
				if(isset($data['name'])){
					$message = str_replace("{name}",$data['name'],$message);
					$subject = str_replace("{name}",$data['name'],$subject);
				}
				if(isset($data['email'])){
					$header = 'Reply-To: <'.$data['email']."> \r\n";
					if($settings['smtp']){$mail_smtp->AddReplyTo($data['email']);}
					$message = str_replace("{email}",$data['email'],$message);
					$subject = str_replace("{email}",$data['email'],$subject);
				}
				if(isset($data['message'])){
					$message = str_replace("{message}",$data['message'],$message);
					$subject = str_replace("{message}",$data['message'],$subject);
				}
				if(isset($data['classified_name'])){
					$message = str_replace("{classified_name}",$data['classified_name'],$message);
					$subject = str_replace("{classified_name}",$data['classified_name'],$subject);
				}
				if(isset($data['classified_url'])){
					$message = str_replace("{classified_url}",$data['classified_url'],$message);
					$subject = str_replace("{classified_url}",$data['classified_url'],$subject);
				}
				if(isset($data['classified_edit_link'])){
					$message = str_replace("{classified_edit_link}",$data['classified_edit_link'],$message);
					$subject = str_replace("{classified_edit_link}",$data['classified_edit_link'],$subject);
				}
				if(isset($data['classified_activate_link'])){
					$message = str_replace("{classified_activate_link}",$data['classified_activate_link'],$message);
					$subject = str_replace("{classified_activate_link}",$data['classified_activate_link'],$subject);
				}
				if(isset($data['classifieds_list'])){
					$classifieds_list = '<ul>';
					foreach($data['classifieds_list'] as $classified){
						$classifieds_list .= '<li><a href="'.absolutePath('classified',$classified['id'],$classified['slug']).'">'.$classified['name'].'</a></li>';
					}
					$classifieds_list .= '</ul>';
					$message = str_replace("{classifieds_list}",$classifieds_list,$message);
					$subject = str_replace("{classifieds_list}",$classifieds_list,$subject);
				}

				$header .= 'From: '.$settings['email'].' <'.$settings['email'].">\r\n";
				$header .= "MIME-Version: 1.0 \r\n";

				if(!$settings['smtp'] and $settings['mail_attachment'] and (!empty($_FILES['attachment']['name']) or !empty($data['document_id']) and document::checkPermissions($data['document_id']))){

					if(!empty($data['document_id'])){
						$document = document::show($data['document_id']);
						$file_tmp_name    = _FOLDER_DOCUMENTS_.$document['url'];
						$file_name        = $document['filename'];
					}else{
						$file_tmp_name    = $_FILES['attachment']['tmp_name'];
						$file_name        = $_FILES['attachment']['name'];
					}
					$semi_rand = md5(time());  
					$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
					$header .= "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"\r\n"; 
					$body = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
					"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";  
					$body .= "--{$mime_boundary}\n"; 
					$fp =    @fopen($file_tmp_name,"rb"); 
					$mdata =  @fread($fp,filesize($file_tmp_name)); 
					@fclose($fp); 
					$mdata = chunk_split(base64_encode($mdata)); 
					$body .= "Content-Type: application/octet-stream; name=\"".$file_name."\"\n" .  
					"Content-Description: ".$file_name."\n" . 
					"Content-Disposition: attachment;\n" . " filename=\"".$file_name."\"; size=".filesize($file_tmp_name).";\n" .  
					"Content-Transfer-Encoding: base64\n\n" . $mdata . "\n\n"; 

				}else{
					$header .= "Content-Type: text/html; charset=UTF-8";
					$body = $message;
				}

				if($settings['smtp']){
					$mail_smtp->Subject = $subject;
					$mail_smtp->Body = $message;
					$mail_smtp->MsgHTML = $message;
					$mail_smtp->AltBody = $message;
					if($settings['mail_attachment'] and !empty($data['document_id']) and document::checkPermissions($data['document_id'])){
						$document = document::show($data['document_id']);
						$mail_smtp->AddAttachment(_FOLDER_DOCUMENTS_.$document['url'],$document['filename']);
					}elseif($settings['mail_attachment'] and !empty($_FILES['attachment']['name'])){
						$mail_smtp->AddAttachment($_FILES['attachment']['tmp_name'],$_FILES['attachment']['name']);
					}
					$mail_smtp->ClearAllRecipients();
					$mail_smtp->AddAddress($email);

					if(!empty($data['newsletter_cancel_code'])){
						$mail_smtp->AddCustomHeader("List-Unsubscribe: <mailto:".$settings['email']."?subject=Unsubscribe>, <".$settings['base_url']."?newsletter_cancel=".$data['newsletter_cancel_code'].">");
					}

					if($mail_smtp->Send()){
						$mail_sent = true;
					}
				}else{

					if(!empty($data['newsletter_cancel_code'])){
						$header .= ("List-Unsubscribe: <mailto:".$settings['email']."?subject=Unsubscribe>, <".$settings['base_url']."?newsletter_cancel=".$data['newsletter_cancel_code'].">");
					}

					$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
					if(mail($email, $subject, $body, $header)){
						$mail_sent = true;
					}
				}
			}
		}
		if($mail_sent){
			logsMail::add($email,$type,$message,$ip);
			return true;
		}else{
			return false;
		}
	}
}
