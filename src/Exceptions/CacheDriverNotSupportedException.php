<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class CacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class CacheDriverNotSupportedException extends CacheException
{
    public const MESSAGE = 'Cache driver not supporting.';
    public const CODE = 406;
}
