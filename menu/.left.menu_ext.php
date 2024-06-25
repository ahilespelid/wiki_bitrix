<? define("NOT_CHECK_PERMISSIONS",true); define("NO_KEEP_STATISTIC", true);
///*/ahilespelid Создание меню left///*/

///*/Выбираем значение структур поля TYPE инфоблока 4///*/
$type_enums = CIBlockPropertyEnum::GetList(["SORT"=>"ASC"], ["IBLOCK_ID" => 4,"CODE" => "TYPE"]);
while($fetch= $type_enums->fetch()){$sub_menu[] = $fetch;}
$sub_menu = array_reverse($sub_menu);
///*/Формируем из TYPE подменю///*/
foreach($sub_menu as $keySubmenu => $itemSubmenu){
    $duplMenuLinks[] = [
            $itemSubmenu['VALUE'],
            '?'.http_build_query(['frame_type' => $itemSubmenu['ID']]),
            [],['FROM_IBLOCK' => 1, 'IS_PARENT' => 1, 'DEPTH_LEVEL' => 1]
    ];
///*/Выбираем категории по TYPE для третьего уровня меню///*/
    $resSubSubmenu = \CIBlockElement::GetList(
        ['SORT'             => 'ASC'],
        ['PROPERTY_25'      => $itemSubmenu['ID']], false, false, ['PROPERTY_BRAND']
    ); while($obSubSubmenu = $resSubSubmenu->fetch()){$brands[] = $obSubSubmenu['PROPERTY_BRAND_VALUE'];}
///*/Формируем меню третьего уровня///*/
    $brands = array_unique($brands);    
    foreach($brands as $keySubSubmenu => $itemSubSubmenu){
        $duplMenuLinks[] = [
                $itemSubSubmenu,
                '?'.http_build_query(['brand' => $itemSubSubmenu,'frame_type' => $itemSubmenu['ID']]),
                [],['FROM_IBLOCK' => 1, 'IS_PARENT' => 0, 'DEPTH_LEVEL' => 2]
        ];
}}
///*/Передаём подменю битрикссу///*/
$aMenuLinks = array_merge($aMenuLinks, $duplMenuLinks);
///*/ahilespelid///*/

/*
global $APPLICATION;
$aMenuLinksExt = $APPLICATION->IncludeComponent(
    "bitrix:menu.sections",
    "",
    Array(
        //"ID" => $_REQUEST["ELEMENT_ID"], 
        "IBLOCK_TYPE" => "catalog", 
        "IBLOCK_ID" => "4", 
        "SECTION_URL" => "/trucks/?brand=#NAME#/",
        "CACHE_TIME" => "3600" 
    )
);

$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
*/