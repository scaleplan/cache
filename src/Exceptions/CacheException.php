<?php

namespace Scaleplan\Cache\Exceptions;

/**
 * Class CacheException
 *
 * @package Scaleplan\Cache\Exceptions
 */
class CacheException extends \Exception
{
    public const MESSAGE = 'Ошибка кэша.';
    public const CODE = 400;

    /**
     * DataException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code ?: static::CODE, $previous);
    }
}
