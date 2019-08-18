<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class MemcachedOperationException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class MemcachedOperationException extends MemcachedCacheException
{
    public const MESSAGE = 'Операция с Memcached не удалась.';
    public const CODE = 500;
}
