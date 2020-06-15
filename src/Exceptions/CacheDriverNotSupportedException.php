<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class CacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class CacheDriverNotSupportedException extends CacheException
{
    public const MESSAGE = 'Такой драйвер кэша не поддерживается.';
    public const CODE = 406;
}
