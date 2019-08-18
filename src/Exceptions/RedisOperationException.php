<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class RedisOperationException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class RedisOperationException extends RedisCacheException
{
    public const MESSAGE = 'Операция с Redis не удалась.';
    public const CODE = 500;
}
