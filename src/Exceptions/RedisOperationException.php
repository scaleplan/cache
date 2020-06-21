<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class RedisOperationException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class RedisOperationException extends RedisCacheException
{
    public const MESSAGE = 'cache.redis-failed';
    public const CODE = 500;
}
