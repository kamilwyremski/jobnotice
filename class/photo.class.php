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

class photo
{

	public static function list(int $classified_id)
	{
		global $db;
		$photos = [];
		$sth = $db->prepare('SELECT id, folder, url, thumb FROM ' . _DB_PREFIX_ . 'photo WHERE classified_id=:classified_id ORDER BY position');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$photos[] = $row;
		}
		return $photos;
	}

	public static function addToClassified(int $classified_id = 0, array $photos = [])
	{
		global $db, $settings, $user;

		$where_statement = ' true ';
		if ($settings['photo_add'] and !empty($photos)) {
			foreach ($photos as $photo_id) {
				if (is_numeric($photo_id) and $photo_id > 0) {
					$where_statement .= ' AND id!=' . intval($photo_id) . ' ';
				}
			}
		}
		$sth = $db->prepare('SELECT * from ' . _DB_PREFIX_ . 'photo WHERE classified_id=:classified_id AND (' . $where_statement . ')');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
		foreach ($sth as $row) {
			;
			unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['thumb']);
			unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['url']);
		}
		$sth = $db->prepare('DELETE from ' . _DB_PREFIX_ . 'photo WHERE classified_id=:classified_id and (' . $where_statement . ')');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();

		if ($settings['photo_add'] and !empty($photos)) {

			if ($user->getId() and $user->moderator) {
				$sth = $db->prepare('SELECT * from ' . _DB_PREFIX_ . 'photo WHERE id=:id LIMIT 1');
			} else {
				$sth = $db->prepare('SELECT * from ' . _DB_PREFIX_ . 'photo WHERE id=:id AND user_id=:user_id LIMIT 1');
				$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
			}
			$photo_position = 0;
			foreach ($photos as $key => $value) {
				$sth->bindValue(':id', $value, PDO::PARAM_INT);
				$sth->execute();
				$photo = $sth->fetch(PDO::FETCH_ASSOC);
				if ($photo) {
					if (!$settings['photo_max'] or $photo_position < $settings['photo_max']) {
						$sth2 = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'photo` SET classified_id=:classified_id, position=:position WHERE id=:id');
						$sth2->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
						$sth2->bindValue(':position', $photo_position, PDO::PARAM_INT);
						$sth2->bindValue(':id', $photo['id'], PDO::PARAM_INT);
						$sth2->execute();
					} else {
						$sth2 = $db->prepare('UPDATE `' . _DB_PREFIX_ . 'photo` SET classified_id=0 WHERE id=:id');
						$sth2->bindValue(':id', $photo['id'], PDO::PARAM_INT);
						$sth2->execute();
					}
					$photo_position++;
				}
			}
		}
	}

	public static function add()
	{
		global $db, $settings, $user;

		$path_parts = pathinfo($_FILES['file']['name']);
		$path_parts['extension'] = strtolower($path_parts['extension']);

		if (!in_array($path_parts['extension'], ['jpg', 'jpeg', 'png', 'webp'])) {
			return ['status' => 0, 'info' => trans('Invalid file type')];
		} elseif ($settings['photo_max_size'] and $_FILES["file"]["size"] > $settings['photo_max_size'] * 1024) {
			return ['status' => 0, 'info' => trans('The file size is too large')];
		} else {

			if (!file_exists(_FOLDER_PHOTOS_ . date('Y'))) {
				mkdir(_FOLDER_PHOTOS_ . date('Y'));
			}
			$folder = date('Y') . '/' . date('m') . '/';
			if (!file_exists(_FOLDER_PHOTOS_ . $folder)) {
				mkdir(_FOLDER_PHOTOS_ . $folder);
			}
			chmod(_FOLDER_PHOTOS_ . $folder, 0777);

			$url = substr(slug($path_parts['filename']), 0, 200) . '.' . $path_parts['extension'];
			$i = 0;
			while (file_exists(_FOLDER_PHOTOS_ . $folder . $url)) {
				$url = substr(slug($path_parts['filename']), 0, 200) . '_' . $i . '.' . $path_parts['extension'];
				$i++;
			}

			move_uploaded_file($_FILES['file']['tmp_name'], _FOLDER_PHOTOS_ . $folder . $url);

			static::correctImageOrientation(_FOLDER_PHOTOS_ . $folder . $url);

			if ($path_parts['extension'] == "jpg" || $path_parts['extension'] == "jpeg") {
				$src = imagecreatefromjpeg(_FOLDER_PHOTOS_ . $folder . $url);
			} elseif ($path_parts['extension'] == "webp") {
				$src = imagecreatefromwebp(_FOLDER_PHOTOS_ . $folder . $url);
			} else {
				$src = imagecreatefrompng(_FOLDER_PHOTOS_ . $folder . $url);
				imagesavealpha($src, true);
				$color = imagecolorallocatealpha($src, 0, 0, 0, 127);
				imagefill($src, 0, 0, $color);
			}

			if ($settings['watermark_add'] and $settings['watermark']) {
				$settings['watermark'] = getFullUrl($settings['watermark']);
				$stamp_parts = pathinfo($settings['watermark']);
				$stamp_parts['extension'] = strtolower($stamp_parts['extension']);
				if (in_array($stamp_parts['extension'], ['jpg', 'jpeg', 'gif', 'png'])) {
					if ($stamp_parts['extension'] == "jpg" || $stamp_parts['extension'] == "jpeg") {
						$stamp = imagecreatefromjpeg($settings['watermark']);
					} elseif ($stamp_parts['extension'] == "png") {
						$stamp = imagecreatefrompng($settings['watermark']);
					} else {
						$stamp = imagecreatefromgif($settings['watermark']);
					}
					imagecopy($src, $stamp, imagesx($src) - imagesx($stamp) - 5, imagesy($src) - imagesy($stamp) - 5, 0, 0, imagesx($stamp), imagesy($stamp));
					if ($path_parts['extension'] == "jpg" || $path_parts['extension'] == "jpeg") {
						imagejpeg($src, _FOLDER_PHOTOS_ . $folder . $url, $settings['photo_quality']);
					} else {
						imagepng($src, _FOLDER_PHOTOS_ . $folder . $url);
					}
				}
			}

			list($width, $height) = getimagesize(_FOLDER_PHOTOS_ . $folder . $url);

			if ($settings['photo_max_height'] or $settings['photo_max_width']) {
				$newheight = $height;
				$newwidth = $width;
				if ($settings['photo_max_height'] and $height >= $settings['photo_max_height']) {
					$newheight = $settings['photo_max_height'];
				} else {
					$newheight = $height;
				}
				$newwidth = round($newheight / $height * $width);
				if ($settings['photo_max_width'] and $newwidth >= $settings['photo_max_width']) {
					$newwidth = $settings['photo_max_width'];
				}
				$newheight = round($newwidth / $width * $height);
				$new_image = imagecreatetruecolor($newwidth, $newheight);
				if ($path_parts['extension'] == "png") {
					imagesavealpha($new_image, true);
					$color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
					imagefill($new_image, 0, 0, $color);
				}
				imagecopyresampled($new_image, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
				if ($path_parts['extension'] == "jpg" || $path_parts['extension'] == "jpeg") {
					imagejpeg($new_image, _FOLDER_PHOTOS_ . $folder . $url, $settings['photo_quality']);
				} elseif ($path_parts['extension'] == "webp") {
					imagewebp($new_image, _FOLDER_PHOTOS_ . $folder . $url);
				} else {
					imagepng($new_image, _FOLDER_PHOTOS_ . $folder . $url);
				}
				imagedestroy($new_image);
			}

			if ($height >= 200) {
				$newheight = 200;
			} else {
				$newheight = $height;
			}
			$newwidth = round($newheight / $height * $width);
			$tmp = imagecreatetruecolor($newwidth, $newheight);
			if ($path_parts['extension'] == "png") {
				imagesavealpha($tmp, true);
				$color = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
				imagefill($tmp, 0, 0, $color);
			}
			imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
			$thumb = explode('.', $url)[0] . '_thumb.' . $path_parts['extension'];

			if ($path_parts['extension'] == "jpg" || $path_parts['extension'] == "jpeg") {
				imagejpeg($tmp, _FOLDER_PHOTOS_ . $folder . $thumb, $settings['photo_quality']);
			} elseif ($path_parts['extension'] == "webp") {
				imagewebp($tmp, _FOLDER_PHOTOS_ . $folder . $thumb);
			} else {
				imagepng($tmp, _FOLDER_PHOTOS_ . $folder . $thumb);
			}
			imagedestroy($src);

			chmod(_FOLDER_PHOTOS_ . $folder, 0755);

			$sth = $db->prepare('INSERT INTO `' . _DB_PREFIX_ . 'photo`(`user_id`, `folder`, `thumb`, `url`) VALUES (:user_id,:folder,:thumb,:url)');
			$sth->bindValue(':user_id', $user->getId(), PDO::PARAM_INT);
			$sth->bindValue(':folder', $folder, PDO::PARAM_STR);
			$sth->bindValue(':thumb', $thumb, PDO::PARAM_STR);
			$sth->bindValue(':url', $url, PDO::PARAM_STR);
			$sth->execute();
			$id = $db->lastInsertId();

			return ['status' => 1, 'id' => $id, 'folder' => $folder, 'url' => $url, 'thumb' => $thumb];
		}
	}

	private static function correctImageOrientation($filename)
	{
		if (function_exists('exif_read_data')) {
			$exif = exif_read_data($filename);
			if ($exif && isset($exif['Orientation'])) {
				$orientation = $exif['Orientation'];
				if ($orientation != 1) {
					$img = imagecreatefromjpeg($filename);
					$deg = 0;
					switch ($orientation) {
						case 3:
							$deg = 180;
							break;
						case 6:
							$deg = 270;
							break;
						case 8:
							$deg = 90;
							break;
					}
					if ($deg) {
						$img = imagerotate($img, $deg, 0);
					}
					imagejpeg($img, $filename, 95);
				}
			}
		}
	}

	public static function removeInClassified(int $classified_id)
	{
		global $db;
		$sth = $db->prepare('SELECT * from ' . _DB_PREFIX_ . 'photo WHERE classified_id=:classified_id');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
		foreach ($sth as $row) {
			chmod(_FOLDER_PHOTOS_ . $row['folder'], 0777);
			unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['thumb']);
			unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['url']);
			chmod(_FOLDER_PHOTOS_ . $row['folder'], 0755);
		}
		$sth = $db->prepare('DELETE from ' . _DB_PREFIX_ . 'photo WHERE classified_id=:classified_id');
		$sth->bindValue(':classified_id', $classified_id, PDO::PARAM_INT);
		$sth->execute();
	}
}
