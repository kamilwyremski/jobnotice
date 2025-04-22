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

class sitemap
{

    public static function generateMain()
    {
        global $settings, $links;

        $sitemap_links[] = $settings['base_url'];
        foreach ($links as $link => $url) {
            if (!(($link == 'articles' and !$settings['enable_articles']) or $link == 'article' or $link == 'my_classifieds' or $link == 'edit' or $link == 'clipboard' or $link == 'settings' or $link == 'profile' or $link == 'captcha')) {
                $sitemap_links[] = absolutePath($link);
            }
        }
        static::generateSub('static', $sitemap_links);

        $sitemap_links = [];
        $classifieds = classified::list(5000, [], 'classifieds');
        foreach ($classifieds as $classified) {
            $sitemap_links[] = absolutePath('classified', $classified['id'], $classified['slug']);
        }
        static::generateSub('classified', $sitemap_links);

        if ($settings['enable_articles']) {
            $sitemap_links = [];
            $articles = article::list(5000);
            foreach ($articles as $article) {
                $sitemap_links[] = absolutePath('article', $article['id'], $article['slug']);
            }
            static::generateSub('article', $sitemap_links);
        }

        $sitemap_links = [];
        $infos = info::list(5000);
        foreach ($infos as $info) {
            if ($info['page'] != 'contact') {
                $sitemap_links[] = absolutePath('info', $info['id'], $info['slug']);
            }
        }
        static::generateSub('info', $sitemap_links);

        $sitemap_links = [];
        $categories = category::listAllPlain();
        foreach ($categories as $category) {
            $sitemap_links[] = $settings['base_url'] . '/' . _PREFIX_CATEGORY_ . $category['path'];
        }
        static::generateSub('category', $sitemap_links);

        $sitemap_links = [];
        $states = state::listAll();
        foreach ($states as $state) {
            $sitemap_links[] = $settings['base_url'] . '/' . _PREFIX_STATE_ . $state['slug'];
            if (!empty($state['states'])) {
                foreach ($state['states'] as $state2) {
                    $sitemap_links[] = $settings['base_url'] . '/' . _PREFIX_STATE_ . $state['slug'] . '/' . $state2['slug'];
                }
            }
        }
        static::generateSub('state', $sitemap_links);

        $sitemapFile = dirname(__FILE__) . "/../sitemap.xml";
        chmod($sitemapFile, 0777);

        $fh = fopen($sitemapFile, 'w');

        $html = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="views/' . $settings['template'] . '/css/main-sitemap.xsl"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        fwrite($fh, $html);

        $sitemaps = ['static', 'classified', 'info', 'category', 'state'];
        if ($settings['enable_articles']) {
            $sitemaps[] = 'article';
        }
        foreach ($sitemaps as $sitemap) {

            $entry = "\n
    <sitemap>\n
        <loc>" . $settings['base_url'] . "/sitemap/sitemap_" . $sitemap . ".xml</loc>\n
        <lastmod>" . date(DATE_ATOM) . "</lastmod>\n
    </sitemap>";
            fwrite($fh, $entry);
        }

        $html = '</sitemapindex>';
        fwrite($fh, $html);
        fclose($fh);
    }

    public static function generateSub(string $sitemap_name, array $sitemap_links)
    {
        global $settings;

        $sitemapFile = dirname(__FILE__) . "/../sitemap/sitemap_" . $sitemap_name . ".xml";

        chmod($sitemapFile, 0777);

        $fh = fopen($sitemapFile, 'w');

        $html = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="' . $settings['base_url'] . '/views/' . $settings['template'] . '/css/main-sitemap.xsl"?>
    <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        fwrite($fh, $html);

        foreach ($sitemap_links as $sitemap_link) {
            $entry = "\n";
            $entry .= '<url>';
            $entry .= "\n";
            $entry .= '  <loc>' . $sitemap_link . '</loc>';
            $entry .= "\n";
            $entry .= '  <changefreq>daily</changefreq>';
            $entry .= "\n";
            $entry .= '  <lastmod>' . date(DATE_ATOM) . '</lastmod>';
            $entry .= "\n";
            $entry .= '</url>';
            fwrite($fh, $entry);
        }

        $html = '
        </urlset>';
        fwrite($fh, $html);
        fclose($fh);

        chmod($sitemapFile, 0644);
    }
}