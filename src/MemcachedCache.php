<?php
declare(strict_types=1);

namespace Scaleplan\Cache;

use Scaleplan\Cache\Exceptions\MemcachedCacheException;
use Scaleplan\Cache\Exceptions\MemcachedOperationException;
use Scaleplan\Cache\Structures\CacheStructure;
use Scaleplan\Cache\Structures\TagStructure;
use Scaleplan\Helpers\Helper;
use function Scaleplan\Translator\translate;

/**
 * Class MemcachedCache
 *
 * @package Scaleplan\Cache\Cache
 */
class MemcachedCache implements CacheInterface, \Psr\SimpleCache\CacheInterface
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
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function getCacheConnect() : \Memcached
    {
        if ($this->memcached->getServerList()) {
            return $this->memcached;
        }

        $hostOrSocket = getenv(self::CACHE_HOST_OR_SOCKET_ENV);
        $port = (int)getenv(self::CACHE_PORT_ENV);
        if (!$hostOrSocket || !$hostOrSocket) {
            throw new MemcachedCacheException(translate('cache.memcached-not-enough-data'));
        }

        if ($this->memcached->addServer($hostOrSocket, $port)) {
            return $this->memcached;
        }

        throw new MemcachedCacheException(translate('cache.memcached-connect-failed'));
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
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function get($key, $default = null) : CacheStructure
    {
        return new CacheStructure((array)json_decode($this->getCacheConnect()->get($this->getKey($key)) ?: '', true));
    }

    /**
     * @param TagStructure[] $tags
     *
     * @throws MemcachedCacheException
     * @throws MemcachedOperationException
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

        /** @var TagStructure $tagStructure */
        foreach ($tags as $tagStructure) {
            if (!$tagStructure instanceof TagStructure) {
                continue;
            }

            if (!$this->getCacheConnect()->set($this->getKey($tagStructure->getName()), (string)$tagStructure)) {
                throw new MemcachedOperationException(translate('cache.tags-init-failed'));
            }
        }
    }

    /**
     * @param array $tags
     *
     * @return TagStructure[]
     *
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
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
     * @param null|int|\DateInterval $ttl
     *
     * @throws MemcachedCacheException
     * @throws MemcachedOperationException
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

        if ($ttl instanceof \DateInterval) {
            $ttl = Helper::dateIntervalToSeconds($ttl);
        }

        $ttl = $ttl ?? ((int)getenv(self::CACHE_TIMEOUT_ENV) ?: 0);
        if (!$this->getCacheConnect()->set($this->getKey($key), $strValue, $ttl)) {
            throw new MemcachedOperationException(translate('cache.write-by-key-failed'));
        }
    }

    /**
     * @param string $key
     *
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function delete($key) : void
    {
        $this->getCacheConnect()->delete($this->getKey($key));
    }

    /**
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function clear() : void
    {
        $this->getCacheConnect()->flush();
    }

    /**
     * @param iterable $keys
     * @param null $default
     *
     * @return array
     *
     * @throws MemcachedCacheException
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
        }, $this->getCacheConnect()->getMulti(is_array($keys) ? $keys : iterator_to_array($keys)));
    }

    /**
     * @param iterable $values
     * @param null $ttl
     *
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function setMultiple($values, $ttl = null) : void
    {
        $this->getCacheConnect()->setMulti(is_array($values) ? $values : iterator_to_array($values), $ttl);
    }

    /**
     * @param iterable $keys
     *
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function deleteMultiple($keys) : void
    {
        $this->getCacheConnect()->deleteMulti(is_array($keys) ? $keys : iterator_to_array($keys));
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws MemcachedCacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function has($key) : bool
    {
        $this->getCacheConnect()->get($key);

        return \Memcached::RES_NOTFOUND !== $this->getCacheConnect()->getResultCode();
    }
}
