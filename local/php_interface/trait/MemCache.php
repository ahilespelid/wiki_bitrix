<? namespace Service\Traits;
 
trait MemCache{
    public $mcache, $mcache_prefix='mem_';
    ///*/ahilespelid Методы кеширования///*/
    public function setMemCache(string $k, $v, $prefix = '')   {return ($this->mcache->set(((empty($prefix)) ? $this->mcache_prefix : $prefix).$k, $v, 0, 0)) ? $v : null;}
    public function getMemCache(string $k, $prefix = '')       {return (empty($v = $this->mcache->get(((empty($prefix)) ? $this->mcache_prefix : $prefix).$k))) ? null : $v;}
    public function delMemCache(string $k, $prefix = '')       {return $this->mcache->delete(((empty($prefix)) ? $this->mcache_prefix : $prefix).$k);}
    public function flushMemCache(string $expires='')          {return $this->mcache->flush((empty($expires)) ? null : $expires);}
}
