<?

$query_element = \CIBlockElement::GetList(["SORT"=>"ASC"], ["IBLOCK_ID" => 1, "CODE" => '5'], false, false, []);
$element = $query_element->fetch();


$seo = new \Bitrix\Iblock\InheritedProperty\ElementValues(1, $element['ID']);
$seo = $seo->getValues();                                                                                         
?>