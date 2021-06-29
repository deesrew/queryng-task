<?php


namespace App\Service;

use ErrorException;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Exception\CacheException;


class MemcachedService
{
    private $connection;
    private $cache;
    private $key;
    private $item;
    private $isCached;

    const NAME_SPACE = '';
    const DEFAULT_LIFE_TIME = 0;
    const EXPIRES_TIME_AFTER = 10;


    /**
     * @throws CacheException
     * @throws ErrorException
     */
    public function __construct(string $key)
    {
        $this->setKey($key);
        $this->setConnection();
        $this->setCacheAdapter();
        $this->setItemCacheKey();
        $this->setIsCached('false');
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return void
     * @throws ErrorException
     */
    public function setConnection()
    {
        $this->connection = MemcachedAdapter::createConnection('memcached://memcached');;
    }

    /**
     * @return mixed
     */
    public function getCacheAdapter()
    {
        return $this->cache;
    }

    /**
     * @throws CacheException
     */
    public function setCacheAdapter(): void
    {
        $this->cache = new MemcachedAdapter(
            $this->connection,
            MemcachedService::NAME_SPACE,
            MemcachedService::DEFAULT_LIFE_TIME
        );
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    public function setItemCacheKey()
    {
        $this->item = $this->cache->getItem($this->key);
    }

    public function getItemCacheKey()
    {
        $this->setIsCached('true');
        return $this->item->get();
    }

    public function checkItemCacheKey()
    {
        if ($result = $this->item->isHit()) {
            $this->setIsCached('true');
        }

        return $result;
    }

    public function saveCache($data)
    {
        $this->item
            ->set($data)
            ->expiresAfter(MemcachedService::EXPIRES_TIME_AFTER);

        $this->cache->save($this->item);
    }

    /**
     * @param string $isCachedStr
     */
    public function setIsCached(string $isCachedStr)
    {
        $this->isCached = $isCachedStr;
    }

    /**
     * @return string
     */
    public function getIsCached(): string
    {
        return $this->isCached;
    }

}