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

if (!isset($settings['base_url'])) {
  die('Access denied!');
}

if ($admin->is_logged()) {

  if (!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action'] == 'save_settings_black_list' and checkToken('admin_save_settings_black_list')) {

    $_POST['black_list_email'] = array_map('trim', array_filter(explode(PHP_EOL, $_POST['black_list_email'])));
    asort($_POST['black_list_email']);
    $_POST['black_list_email'] = implode(PHP_EOL, array_unique($_POST['black_list_email']));

    $_POST['black_list_ip'] = array_map('trim', array_filter(explode(PHP_EOL, $_POST['black_list_ip'])));
    asort($_POST['black_list_ip']);
    $_POST['black_list_ip'] = implode(PHP_EOL, array_unique($_POST['black_list_ip']));

    settings::saveArrays(['black_list_email', 'black_list_ip', 'black_list_words']);

    getSettings();
    $render_variables['alert_success'][] = trans('Changes have been saved');
  }

  $title = trans('Black list') . ' - ' . $title_default;

}
