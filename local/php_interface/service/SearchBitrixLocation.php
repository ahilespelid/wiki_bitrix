<? namespace Service;

use \Service\DadataOrCache;
use \Service\Traits\MemCache;
use \Service\SearchBitrixLocationResponse;

class SearchBitrixLocation extends SearchBitrixLocationResponse { 
    use MemCache;
    public function __construct(public $mcache){
        $this->mcache = new \Memcache; $this->mcache->connect('127.0.0.1', 11211);
    }

    // Метод вариантов городов по названию
    public function getBitrixCitySuggest($obj)
    {
        if (!empty($obj)) 
        {
            global $times;

            $key = hash('sha256', mb_convert_case($obj, MB_CASE_LOWER, 'UTF-8'));
            // Очищаем Memcache
            $this->mcache->flush();
    
            // Проверяем, есть ли значение в Memcache
            if (empty($city = (empty($cache = $this->getMemCache($key))) ? [] : $cache))
            {
                // Получаем города из Bitrix
                $city_unprocessed = $this->getBitrixLocations($obj, 12);
    
                // Обрабатываем каждый город
                foreach ($city_unprocessed as $k => $c)
                {
                    // Приводим ключи массива к нижнему регистру
                    $c['data'] = array_change_key_case($c['data'], CASE_LOWER);
    
                    // Получаем полный адрес города
                    $full_address = $this->getFullAdressCityFromLocationID($c['data']['id']);
                    if (!is_null($full_address)) {
                        // Добавляем полный адрес к данным города
                        $c['data'] = $c['data'] + $full_address;
                    }
                    $city[$k]['data'] = $c['data'];
                }
    
                // Сортируем города по определенному критерию
                // usort($city, function($a) use ($obj) {
                //     return (mb_strtolower($obj) == mb_strtolower($a['data']['city'])) ? -1 : ((5 == $a['data']['type_id']) ? -1 : 1);
                // });
    
                // Сохраняем результат в Memcache
                if (!empty($city)) {
                    $this->setMemCache($key, $city);
                }
            }
        }
        return (empty($city)) ? null : $city;
    }

    // Метод получает из Битрикса код города
    public function getBitrixCityFromIP($obj)
    {
        if (!empty($obj)) 
        {
            // Получаем код города из GeoIP
            $code = $this->getBitrixCodeFromGeoIp($obj);
    
            // Получаем информацию о городе из Битрикса
            $city = $this->getBitrixLocation(
                [
                    '=NAME.LANGUAGE_ID' => 'RU', 
                    '=CODE' => $code, 
                    'TYPE_ID' => [5, 6]
                ],
                ['ID', 'bxcode' => 'CODE', 'city' => 'NAME.NAME']
            );
    
            // Обрабатываем результат
            if (!empty($city) && is_array($city)) {
                $city = array_change_key_case($city, CASE_LOWER);
    
                // Получаем полный адрес города
                $full_address = $this->getFullAdressCityFromLocationID($city['id']) ?? [];
                $city += $full_address;
    
                $city = array_change_key_case($city, CASE_UPPER);
            }
        }
        return (empty($city)) ? null : [['data' => $city]];
    }

    // Метод получения CODE города из Битрикса по названию
    public function getBitrixLocationCode($obj)
    {
        if (!empty($obj)) 
        {
            // Инициализируем кэш
            $this->bcache_prefix = 'location/getBitrixLocationCode';
            $this->bcache->initCache($this->expires, $obj, $this->bcache_prefix);
    
            // Проверяем, есть ли значение в кэше
            if (empty($city = ($cache = $this->isBitrixCache()) ? $cache : [])) {
                // Получаем информацию о городе из Битрикса
                $city = $this->getBitrixLocation(['=NAME.NAME' => $obj, 'TYPE_ID' => 5], ['CODE']);
                // Сохраняем результат в кэш
                $this->setBitrixCache($city);
            }
        }
        return (empty($city)) ? null : $city;
    }
        
    /*
     * ahilespelid 
     * Метод выбирает города по ajax битрикса 
     * через метод $this->curlSaleLocationSearch()
     */
    public function getBitrixCitySuggestAjax($obj){
        //$this->bcache_prefix = 'location/getBitrixCitySuggestAjax';
        //$this->bcache->initCache($this->expires, $obj, $this->bcache_prefix);   // $this->flushBitrixCache();
        if(empty($location = ($cache = $this->isBitrixCache()) ? $cache : [])){
            $location_unprocessed = $this->curlSaleLocationSearch($obj);
            if(1 == $location_unprocessed['result']){
                $location = []; foreach($location_unprocessed['data']['ITEMS'] as $k => $loc){
                    $location[$k] = ['id' => $loc['ID'], 'bxcode' => $loc['CODE'], 'city' => $loc['DISPLAY']]+$this->getFullAdressCityFromLocationID($loc['ID']) ?? []; //$location[$k]['postal_code'] = $this->getZip($loc['ID']);
                }$this->setBitrixCache($location);}else{$location = null; $this->flushBitrixCache();}}
    return (empty($location)) ? null : $location;}


    /*
     * ahilespelid 
     * метод получает из дадаты или кеша адрес по индексу 
     * выбирает оттуда город и берёт код битрикса по городу
     */ 
    public function getBitrixCityFromZIP($obj){
        if(!empty($obj))
        {
            foreach((new DadataOrCache)->getSuggest($obj) as $dadata){
                $city_unprocessed[] = array_map('trim', explode(',', $dadata['value']));
            }  
            $city_unprocessed = ($_is_array_full = (is_array($city_unprocessed) && !empty($city_unprocessed))) ? 
                array_values(array_filter(array_unique(array_merge(...$city_unprocessed)), fn($i) => str_starts_with(strtolower($i), 'г'))) : $location; 
            $city_unprocessed = ($_is_array_full) ? str_replace('г ', '', $city_unprocessed[0]) : $city_unprocessed;
            if($city = (!empty($city_unprocessed) && is_string($city_unprocessed)) ? ['CITY' => $city_unprocessed] : null)
            {
                if(!empty($code = $this->getBitrixLocationCode($city['CITY'])))
                {
                    $city = ['CODE' => $code['CODE'], 'CITY' => $city['CITY']];
                }
            }
        }
        return (empty($city)) ? null : $city;
    }

    /*
     * ahilespelid 
     * метод получает из дадаты или кеша адрес 
     * по IP выбирает оттуда город и берёт код битрикса по городу
     */    
    public function getBitrixWithDadataCityFromIP($obj){
        if(!empty($obj))
        { 
            $location = (array)(new DadataOrCache)->getCityFromIP($obj); 
            $location = ($location['value'] ?? null) ? str_replace('г ', '', $location['value']) : $location['value'];
        }
        return (is_array($ret = $this->getBitrixLocationCode($location))) ? $ret+['CITY' => $location] : null;
    }

}