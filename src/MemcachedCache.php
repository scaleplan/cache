<?php
declare(strict_types=1);

namespace Scaleplan\Cache;

use Scaleplan\Cache\Exceptions\MemcachedCacheException;
use Scaleplan\Cache\Exceptions\MemcachedOperationException;
use Scaleplan\Cache\Structures\CacheStructure;
use Scaleplan\Cache\Structures\TagStructure;

/**
 * Class MemcachedCache
 *
 * @package Scaleplan\Cache\Cache
 */
class MemcachedCache implements CacheInterface
{
    public const PERSISTENT_ID = 589475;

    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * @var string
     */
    protected $databaseKeyPostfix;

    /**
     * MemcachedCache constructor.
     *
     * @param bool $isPconnect
     */
    public function __construct(bool $isPconnect = null)
    {
        $this->memcached = ($isPconnect ?? (bool)getenv(self::CACHE_PCONNECT_ENV))
            ? new \Memcached(static::PERSISTENT_ID)
            : new \Memcached();
    }

    /**
     * @return \Memcached
     *
     * @throws MemcachedCacheException
     */
    public function getCacheConnect() : \Memcached
    {
        if ($this->memcached->getServerList()) {
            return $this->memcached;
        }

        $hostOrSocket = getenv(self::CACHE_HOST_OR_SOCKET_ENV);
        $port = (int)getenv(self::CACHE_PORT_ENV);
        if (!$hostOrSocket || !$hostOrSocket) {
            throw new MemcachedCacheException('Недостаточно даных для подключения к Memcached.');
        }

        if ($this->memcached->addServer($hostOrSocket, $port)) {
            return $this->memcached;
        }

        throw new MemcachedCacheException('Не удалось подключиться к Memcached');
    }

    /**
     * @param string|null $dbName
     */
    public function selectDatabase(?string $dbName) : void
    {
        $this->databaseKeyPostfix = $dbName;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getKey(string $key) : string
    {
        return "$key:{$this->databaseKeyPostfix}";
    }

    /**
     * @param string $key
     *
     * @return CacheStructure
     *
     * @throws MemcachedCacheException
     */
    public function get(string $key) : CacheStructure
    {
        return new CacheStructure((array)json_decode($this->getCacheConnect()->get($this->getKey($key)) ?: '', true));
    }

    /**
     * @param TagStructure[] $tags
     *
     * @throws MemcachedCacheException
     * @throws MemcachedOperationException
     */
    public function initTags(array $tags) : void
    {
        if (!$tags) {
            return;
        }

        /** @var TagStructure $tagStructure */
        foreach ($tags as $tagStructure) {
            if (!$tagStructure instanceof TagStructure) {
                continue;
            }

            if (!$this->getCacheConnect()->set($this->getKey($tagStructure->getName()), (string)$tagStructure)) {
                throw new MemcachedOperationException('Операция инициализации тегов не удалась.');
            }
        }
    }

    /**
     * @param array $tags
     *
     * @return TagStructure[]
     *
     * @throws MemcachedCacheException
     */
    public function getTagsData(array $tags) : array
    {
        $result = [];
        foreach ($tags as $tag) {
            $tagData = json_decode($this->getCacheConnect()->get($this->getKey($tag)), true);
            if (!$tag) {
                continue;
            }

            $result[$tag] = new TagStructure($tagData);
            $result[$tag]->setName($tag);
        }

        return $result;
    }

    /**
     * @param string $key
     * @param CacheStructure $value
     * @param int $ttl
     *
     * @throws MemcachedCacheException
     * @throws MemcachedOperationException
     */
    public function set(string $key, CacheStructure $value, int $ttl = null) : void
    {
        if ($value instanceof \Serializable) {
            $strValue = $value->serialize();
        } else {
            $strValue = (string)$value;
        }

        $ttl = $ttl ?? ((int)getenv(self::CACHE_TIMEOUT_ENV) ?: 0);
        if (!$this->getCacheConnect()->set($this->getKey($key), $strValue, $ttl)) {
            throw new MemcachedOperationException('Операция записи по ключу не удалась.');
        }
    }

    /**
     * @param string $key
     *
     * @throws MemcachedCacheException
     */
    public function delete(string $key) : void
    {
        $this->getCacheConnect()->delete($this->getKey($key));
    }
}
