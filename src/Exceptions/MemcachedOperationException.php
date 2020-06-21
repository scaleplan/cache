<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class MemcachedOperationException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class MemcachedOperationException extends MemcachedCacheException
{
    public const MESSAGE = 'cache.memcached-failed';
    public const CODE = 500;
}
