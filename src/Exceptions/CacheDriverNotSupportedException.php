<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class CacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class CacheDriverNotSupportedException extends CacheException
{
    public const MESSAGE = 'cache.driver-not-supported';
    public const CODE = 406;
}
