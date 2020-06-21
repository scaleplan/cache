<?php
declare(strict_types=1);

namespace Scaleplan\Cache\Structures;

use Scaleplan\Cache\Exceptions\CacheException;
use Scaleplan\InitTrait\InitTrait;
use function Scaleplan\Translator\translate;

/**
 * Class TagStructure
 *
 * @package Scaleplan\Cache
 */
class TagStructure
{
    use InitTrait;

    /**
     * @var int
     */
    private $time = 0;

    /**
     * @var int
     */
    private $maxId = 0;

    /**
     * @var int
     */
    private $minId = 0;

    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * TagStructure constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->initObject($data);
    }

    /**
     * @return int
     */
    public function getTime() : int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time) : void
    {
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getMaxId() : int
    {
        return $this->maxId;
    }

    /**
     * @param int $maxId
     */
    public function setMaxId(int $maxId) : void
    {
        $this->maxId = $maxId;
    }

    /**
     * @return int
     */
    public function getMinId() : int
    {
        return $this->minId;
    }

    /**
     * @param int $minId
     */
    public function setMinId(int $minId) : void
    {
        $this->minId = $minId;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            'max_id' => $this->getMaxId(),
            'min_id' => $this->getMinId(),
            'time'   => $this->getTime(),
        ];
    }

    /**
     * @return string
     *
     * @throws CacheException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function __toString()
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new CacheException(translate('cache.serialize-failed'));
        }

        return (string)$json;
    }
}
