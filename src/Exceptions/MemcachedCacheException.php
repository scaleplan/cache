<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class MemcachedCacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class MemcachedCacheException extends CacheException
{
    public const MESSAGE = 'cache.memcached-error';
}
