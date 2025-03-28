<? namespace Service; require_once $_SERVER['DOCUMENT_ROOT'].'/composer/vendor/autoload.php';
\Bitrix\Main\Loader::includeModule('landing');

use \Bitrix\Main\Loader;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\DB\SqlExpression;

use \Bitrix\Sale\Location;
use \Bitrix\Sale\Location\GeoIp;
use \Bitrix\Sale\Location\LocationTable;
use \Bitrix\Sale\Location\Admin\LocationHelper;
use \Bitrix\Sale\Location\ExternalTable;

use \Bitrix\Landing\Domain;

use \Service\DadataOrCache;
use \Service\Traits\DenySymbolFolderNames;
use \Service\Traits\BitrixCache;

Loader::includeModule('sale');

class SearchBitrixLocationResponse{ 
    use BitrixCache; 
    use DenySymbolFolderNames;
    public function __construct(public $bcache_prefix = 'SearchBitrixLocation', public $expires = 36400){
        $this->bcache = Cache::createInstance(); 
    }

///*/ahilespelid метод по location id генерирует ответ в виде массива [city => наименование города, city_stack => остаток от адреса]///*/        
    public function getFullAdressCityFromLocationID($ID):?array{
        if(empty($ID)){return null;} $adress_array = explode(',', LocationHelper::getLocationStringById($ID));
        $CITY_NAME  = array_pop($adress_array);
        $CITY_STACK = ''; for($f = $i=count($adress_array)-1; $i>=0; $i--){$CITY_STACK .= trim($adress_array[$i]).((0==$i) ? '' : ', ');}
    return ['city' => $CITY_NAME, 'city_stack' => $CITY_STACK];}
///*/panenco метод выбора индекса по location id///*/
    public function getZip($locationId){
        $zip = ExternalTable::getList([
            'filter' => ['=LOCATION_ID' => $locationId, '=SERVICE_ID' => 4],
            'select' => ['XML_ID'],
            'order' => ['XML_ID' => 'ASC'],
            'limit' => 1
        ])->fetch()['XML_ID'];
    return (empty($zip)) ? null : $zip;}
///*/ahilespelid пара методов запроса города из битрикса по аякс///*/     
    /*public function curlSaleLocationSearch($obj){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://z51.ru/bitrix/components/bitrix/sale.location.selector.search/get.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'select[1]=ID&select[2]=CODE&select[3]=TYPE_ID&select[DISPLAY]=NAME.NAME&additionals[1]=PATH&filter[%3DPHRASE]='.urlencode($obj).'&filter[%3DNAME.LANGUAGE_ID]=ru&filter[%3DSITE_ID]=s1&version=2&PAGE_SIZE=10&PAGE=0');
        $response = curl_exec($ch); curl_close($ch);
        $json = \OviDigital\JsObjectToJson\JsConverter::convertToJson($response);
    return $ret = ($array = is_json($json)) ? $array : null;}*/
///*/ahilespelid метод запроса локации из битрикса///*/    
    public function getBitrixLocation($filter, $select, $order = []){
        $q = ['filter' => $filter, 'select' => $select]; if(!empty($order)){$q['order'] = $order;}
        $location = LocationTable::getList($q)->fetch();
        if(!empty($location['ID'])){$location['postal_code'] = $this->getZip($location['ID']);}
    return (empty($location)) ? null : $location;}

    // Метод запроса локаций из Битрикса
    public function getBitrixLocations($obj, $limit = 12)
    {
        $preids = $ids = [];
        // Очищаем строку от символов пунктуации и цифр
        $obj =  preg_replace('/[\p{P}\d]/u', '', $obj);
    
        try {
            $find = \Bitrix\Sale\Location\Search\Finder::find([
                'filter' => ['=PHRASE' => $obj]
            ]);
            $preids = $find->fetchAll();
        } catch (Exception $e) {
            return null;
        }
    
        // Извлекаем идентификаторы локаций
        if (!empty($preids) && is_array($preids)) {
            $ids = array_slice(array_column($preids, 'ID'), 0, $limit ?? 12);
        }
    
        // Получаем информацию о локациях
        if (!empty($ids)) {
            $q = [
                'filter' => [
                    '=NAME.LANGUAGE_ID' => 'ru',
                    'ID' => $ids,
                    'TYPE_ID' => [5, 6]
                ],
                'select' => ['ID', 'bxcode' => 'CODE', 'city' => 'NAME.NAME', 'TYPE_ID'],
                // 'order' => $order,
                'limit' => $limit
            ];
            $res = LocationTable::getList($q);
            while ($item = $res->fetch()) {
                $location[$item['ID']] = ['data' => $item /*+['postal_code'] = $this->getZip($item['ID'])*/];
            }
        }
        return (empty($location)) ? null : array_replace_recursive(array_flip($ids), $location);
    }
    
    // Метод выбирает code по IP
    public function getBitrixCodeFromGeoIp($obj)
    {
        return GeoIp::getLocationCode($obj);
    }
    
}
