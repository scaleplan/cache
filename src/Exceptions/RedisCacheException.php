<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class RedisCacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class RedisCacheException extends CacheException
{
    public const MESSAGE = 'Ошибка Redis.';
}
