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

if (!isset($settings['base_url'])) {
    die('Access denied!');
}

if (!empty($_GET['slug'])) {
    throw new noFoundException();
}

function generateCaptcha()
{
    $chars = '123456789qwertyuipasdfghjklzxcvbnm';
    $width = 120;
    $height = 30;
    $number_of_characters = 6;
    $str = '';

    for ($i = 0; $i < $number_of_characters; $i++)
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);

    $string = $str;
    $_SESSION['captcha'] = $string;

    $im = imagecreate($width, $height);

    $background = imagecolorallocate($im, 0, 0, 0);
    $font = imagecolorallocate($im, 177, 177, 177);
    $net = imagecolorallocate($im, 78, 78, 78);
    $frame = imagecolorallocate($im, 131, 131, 131);

    imagefill($im, 1, 1, $background);

    for ($i = 0; $i < 1600; $i++) {
        $rand1 = rand(0, $width);
        $rand2 = rand(0, $height);
        imageline($im, $rand1, $rand2, $rand1, $rand2, $net);
    }

    $x = rand(5, intval($width / (7 / 2)));

    imagerectangle($im, 0, 0, $width - 1, $height - 1, $frame);

    for ($a = 0; $a < 7; $a++) {
        imagestring($im, 6, $x, rand(4, $height / 5), substr($string, $a, 1), $font);
        $x += (5 * 3);
    }
    header("Content-type: image/gif");
    imagegif($im);
    imagedestroy($im);
    die();
}
generateCaptcha();
