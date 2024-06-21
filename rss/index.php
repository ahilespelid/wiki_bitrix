<?php ///*/ahilespelid///*/
//настройка параметров скрипта
date_default_timezone_set('Europe/Moscow');
include_once '../local/php_interface/functions.php';
define("NOT_CHECK_PERMISSIONS",true);
define("NO_KEEP_STATISTIC", true);

//объявляем необходимые переменные
$domain = (isset($_SERVER['SERVER_NAME'])) ? (('on' == $_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'] : 'https://aksaysport.ru';
$host   = $_SERVER['DOCUMENT_ROOT'] = (empty($_SERVER['DOCUMENT_ROOT'])) ? realpath(__DIR__.DIRECTORY_SEPARATOR.'..') : $_SERVER['DOCUMENT_ROOT'];
$arNews = []; $nd = 'no data available'; $rssPath = 'news.xml';

//Подключаем битрикс
require_once $host . "/bitrix/modules/main/include/prolog_before.php";
\Bitrix\Main\Loader::includeModule('iblock');

//генерируем xml из инфоблока новостей
$rss = '<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" version="2.0">
    <channel>
        <title>Новости</title>
        <link>'.$domain.'</link>
        <description></description>
        <lastBuildDate>'.date('r').'</lastBuildDate>
        <ttl>1</ttl>
';
$resNEWS = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => 1], false);
if (0 < $resNEWS->selectedRowsCount()){
    while($obNEWS = $resNEWS->fetch()){
        if(is_numeric($obNEWS['PREVIEW_PICTURE'])){
            $pic        = $domain.($ppic = \CFile::getpath($obNEWS['PREVIEW_PICTURE']));
            $gis_pic    = getimagesize($pic); 
            $size_pic   = filesize($host.$ppic); 
        }
       $rss.= 
'<item>
<title>'        .(empty($obNEWS['PREVIEW_TEXT'])                 ? $nd : $obNEWS['PREVIEW_TEXT']).'</title>
<guid>'         .(empty($obNEWS['ID'])                           ? $nd : $obNEWS['ID']).'</guid>
<pubDate>'      .(($date = is_date($obNEWS['DATE_CREATE_UNIX'])) ? $date->format('r') : $nd).'</pubDate>
<link>'         .(empty($obNEWS['DETAIL_PAGE_URL'])              ? $nd : str_replace('#CODE#', $obNEWS['CODE'], 
                                                                         str_replace('#SITE_DIR#', $domain, 
                                                                                                    $obNEWS['DETAIL_PAGE_URL']))).'</link>'.
//<pdalink>mobile version link</pdalink>
'<description>' .(empty($obNEWS['NAME'])                         ? $nd : $obNEWS['NAME']).'</description>
<category>'     .(empty($obNEWS['IBLOCK_NAME'])                  ? $nd : $obNEWS['IBLOCK_NAME']).'</category>
<enclosure url="'.$pic.'" length="'.$size_pic.'" type="'.$gis_pic['mime'].'"/>
<content:encoded>'.(empty($obNEWS['DETAIL_TEXT'])                 ? $nd : '<![CDATA['.$obNEWS['DETAIL_TEXT'].']]>').'</content:encoded>
</item>';
        $arNews[] = $obNEWS;
}}
$rss.= '</channel></rss>';

//сравниваем даты для генерации нового файла    
$max_DATE_CREATE_UNIX = max(array_column($arNews, 'DATE_CREATE_UNIX'));
if($news_DATE_CREATE = is_date($max_DATE_CREATE_UNIX)){
    if(file_exists($rssPath)){
        $xml                = simplexml_load_file($rssPath);
        $xml_DATE_CREATE    = is_date($xmlDate = (string) $xml->channel->lastBuildDate);

        if($news_DATE_CREATE->getTimestamp() >= $xml_DATE_CREATE->getTimestamp()){
            unlink($rssPath); file_put_contents($rssPath, $rss);
        }
    } else {
        file_put_contents($rssPath, $rss);
    }
}

//вывод
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rss);
///*/ahilespelid///*/