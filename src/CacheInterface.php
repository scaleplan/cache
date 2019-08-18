<?php

namespace Scaleplan\Cache;

use Scaleplan\Cache\Structures\CacheStructure;

/**
 * Interface CacheInterface
 *
 * @package Scaleplan\Cache\Cache
 */
interface CacheInterface
{
    public const CACHE_PCONNECT_ENV       = 'CACHE_PCONNECT';
    public const CACHE_HOST_OR_SOCKET_ENV = 'CACHE_HOST_OR_SOCKET';
    public const CACHE_PORT_ENV           = 'CACHE_PORT';
    public const CACHE_TIMEOUT_ENV        = 'CACHE_TIMEOUT';

    /**
     * @return \Redis|\Memcached
     */
    public function getCacheConnect();

    /**
     * Инициализация заданного массива тегов
     *
     * @param array $tags
     */
    public function initTags(array $tags) : void;

    /**
     * Возвращает массив времен актуальности тегов асоциированных с запросом
     *
     * @param array $tags
     *
     * @return array
     */
    public function getTagsData(array $tags) : array;

    /**
     * @param string $key
     *
     * @return CacheStructure
     */
    public function get(string $key) : CacheStructure;

    /**
     * @param string $key
     * @param CacheStructure $value
     * @param int|null $ttl
     */
    public function set(string $key, CacheStructure $value, int $ttl = null) : void;

    /**
     * @param string $key
     */
    public function delete(string $key) : void;

    /**
     * @param string|null $dbName
     */
    public function selectDatabase(?string $dbName) : void;
}
