<?php
declare(strict_types=1);

namespace Scaleplan\Cache;

use Scaleplan\Cache\Exceptions\RedisCacheException;
use Scaleplan\Cache\Exceptions\RedisOperationException;
use Scaleplan\Cache\Structures\CacheStructure;
use Scaleplan\Cache\Structures\TagStructure;
use function Scaleplan\Translator\translate;

/**
 * Class RedisCache
 *
 * @package Scaleplan\Cache\Cache
 */
class RedisCache implements CacheInterface, \Psr\SimpleCache\CacheInterface
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
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
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
            throw new RedisCacheException(translate('cache.redis-not-enough-data'));
        }

        if ($this->isPconnect) {
            $connectMethod = 'pconnect';
        } else {
            $connectMethod = 'connect';
        }

        if ($this->redis->$connectMethod($hostOrSocket, $port, $timeout, static::RESERVED, static::RETRY_INTERVAL)) {
            return $this->redis;
        }

        throw new RedisCacheException(translate('cache.connect-to-host-failed', ['host-or-socket' => $hostOrSocket]));
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
     * @param null $default
     *
     * @return CacheStructure
     *
     * @throws RedisCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function get($key, $default = '') : CacheStructure
    {
        return new CacheStructure(
            (array)json_decode($this->getCacheConnect()->get($this->getKey($key)) ?: $default, true)
        );
    }

    /**
     * @param TagStructure[] $tags
     *
     * @throws RedisCacheException
     * @throws RedisOperationException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
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
            throw new RedisOperationException(translate('cache.tags-init-failed'));
        }
    }

    /**
     * @param array $tags
     *
     * @return TagStructure[]
     *
     * @throws RedisCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
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
     * @param null|int|\DateInterval $ttl
     *
     * @throws RedisCacheException
     * @throws RedisOperationException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function set($key, $value, $ttl = null) : void
    {
        if ($value instanceof \Serializable) {
            $strValue = $value->serialize();
        } else {
            $strValue = (string)$value;
        }

        $ttl = $ttl ?? (int)$this->redis->getTimeout();
        if (!$this->getCacheConnect()->set($this->getKey($key), $strValue, $ttl)) {
            throw new RedisOperationException(translate('cache.write-by-key-failed'));
        }
    }

    /**
     * @param string $key
     *
     * @throws RedisCacheException
     * @throws RedisOperationException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function delete($key) : void
    {
        if (!$this->getCacheConnect()->del($this->getKey($key))) {
            throw new RedisOperationException(translate('cache.delete-by-key-failed'));
        }
    }

    /**
     * @throws RedisCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function clear() : void
    {
        $this->getCacheConnect()->flushDB();
    }

    /**
     * @param iterable $keys
     * @param null $default
     *
     * @return array|iterable
     *
     * @throws RedisCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function getMultiple($keys, $default = null) : array
    {
        return array_map(static function ($value) use ($default) {
            return $value === false ? $default : $value;
        }, $this->getCacheConnect()->mget(is_array($keys) ? $keys : iterator_to_array($keys)));
    }

    public function setMultiple($values, $ttl = null) : void
    {
        $this->getCacheConnect()->mset(is_array($values) ? $values : iterator_to_array($values));
    }

    public function deleteMultiple($keys) : void
    {
        $this->getCacheConnect()->del(is_array($keys) ? $keys : iterator_to_array($keys));
    }

    /**
     * @param string $key
     *
     * @return bool|void
     * @throws RedisCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function has($key) : bool
    {
        $this->getCacheConnect()->exists($key);
    }
}
