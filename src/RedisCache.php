<?php
declare(strict_types=1);

namespace Scaleplan\Cache;

use Scaleplan\Cache\Exceptions\RedisCacheException;
use Scaleplan\Cache\Exceptions\RedisOperationException;
use Scaleplan\Cache\Structures\CacheStructure;
use Scaleplan\Cache\Structures\TagStructure;

/**
 * Class RedisCache
 *
 * @package Scaleplan\Cache\Cache
 */
class RedisCache implements CacheInterface
{
    public const RESERVED       = '';
    public const RETRY_INTERVAL = 0;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $databaseKeyPostfix;

    /**
     * @var bool
     */
    protected $isPconnect;

    /**
     * RedisCache constructor.
     *
     * @param bool $isPconnect
     */
    public function __construct(bool $isPconnect = null)
    {
        $this->redis = new \Redis();
        $this->isPconnect = $isPconnect ?? (bool)getenv(static::CACHE_PCONNECT_ENV);
    }

    /**
     * @return \Redis
     *
     * @throws RedisCacheException
     */
    public function getCacheConnect() : \Redis
    {
        if ($this->redis->isConnected()) {
            return $this->redis;
        }

        $hostOrSocket = getenv(self::CACHE_HOST_OR_SOCKET_ENV);
        $port = (int)getenv(self::CACHE_PORT_ENV);
        $timeout = (int)getenv(self::CACHE_TIMEOUT_ENV) ?: 0;
        if (!$hostOrSocket || !$hostOrSocket) {
            throw new RedisCacheException('Недостаточно даных для подключения к Redis.');
        }

        if ($this->isPconnect) {
            $connectMethod = 'pconnect';
        } else {
            $connectMethod = 'connect';
        }

        if ($this->redis->$connectMethod($hostOrSocket, $port, $timeout, static::RESERVED, static::RETRY_INTERVAL)) {
            return $this->redis;
        }

        throw new RedisCacheException ("Не удалось подключиться к хосту/сокету $hostOrSocket");
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
     * @throws RedisCacheException
     */
    public function get(string $key) : CacheStructure
    {
        return new CacheStructure((array)json_decode($this->getCacheConnect()->get($this->getKey($key)) ?: '', true));
    }

    /**
     * @param TagStructure[] $tags
     *
     * @throws RedisCacheException
     */
    public function initTags(array $tags) : void
    {
        if (!$tags) {
            return;
        }

        $tagsToSave = [];
        /** @var TagStructure $tagStructure */
        foreach ($tags as $tagStructure) {
            if (!$tagStructure instanceof TagStructure) {
                continue;
            }

            $tagsToSave[$this->getKey($tagStructure->getName())] = (string)$tagStructure;
        }

        if (!$this->getCacheConnect()->mset($tagsToSave)) {
            throw new RedisOperationException('Операция инициализации тегов не удалась.');
        }
    }

    /**
     * @param array $tags
     *
     * @return TagStructure[]
     *
     * @throws RedisCacheException
     */
    public function getTagsData(array $tags) : array
    {
        $result = [];
        $databaseKeys = array_map(function ($value) {
            return $this->getKey($value);
        }, $tags);
        foreach ($this->getCacheConnect()->mget($databaseKeys) ?: [] as $key => $value) {
            $value = \json_decode($value ?: '', true);
            if (!$value) {
                continue;
            }

            $result[$tags[$key]] = new TagStructure($value);
            $result[$tags[$key]]->setName($tags[$key]);
        }

        return $result;
    }

    /**
     * @param string $key
     * @param CacheStructure $value
     * @param int $ttl
     *
     * @throws RedisCacheException
     */
    public function set(string $key, CacheStructure $value, int $ttl = null) : void
    {
        if ($value instanceof \Serializable) {
            $strValue = $value->serialize();
        } else {
            $strValue = (string)$value;
        }

        $ttl = $ttl ?? (int)$this->redis->getTimeout();
        if (!$this->getCacheConnect()->set($this->getKey($key), $strValue, $ttl)) {
            throw new RedisOperationException('Операция записи по ключу не удалась.');
        }
    }

    /**
     * @param string $key
     *
     * @throws RedisCacheException
     * @throws RedisOperationException
     */
    public function delete(string $key) : void
    {
        if (!$this->getCacheConnect()->del($this->getKey($key))) {
            throw new RedisOperationException('Операция удаления по ключу не удалась.');
        }
    }
}
