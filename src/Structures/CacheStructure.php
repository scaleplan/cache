<?php
declare(strict_types=1);

namespace Scaleplan\Cache\Structures;

use Scaleplan\Cache\Exceptions\CacheException;
use Scaleplan\InitTrait\InitTrait;
use Scaleplan\Result\DbResult;
use Scaleplan\Result\Interfaces\ArrayResultInterface;
use Scaleplan\Result\Interfaces\DbResultInterface;

/**
 * Class CacheStructure
 *
 * @package Scaleplan\Cache
 */
class CacheStructure
{
    use InitTrait;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var int
     */
    private $time = 0;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var string
     */
    private $idTag = '';

    /**
     * @var int
     */
    private $maxId = 0;

    /**
     * @var int
     */
    private $minId = 0;

    /**
     * CacheStructure constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->initObject($data);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data) : void
    {
        $this->data = $data;
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
     * @return array
     */
    public function getTags() : array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags) : void
    {
        $this->tags = $tags;
    }

    /**
     * @return string
     */
    public function getIdTag() : string
    {
        return $this->idTag;
    }

    /**
     * @param string $idTag
     */
    public function setIdTag(string $idTag) : void
    {
        $this->idTag = $idTag;
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
     * @return string
     *
     * @throws CacheException
     */
    public function __toString()
    {
        $data = (string)$this->data;
        if ($this->data instanceof ArrayResultInterface) {
            $data = $this->data->getResult();
        }

        if (is_array($this->data)) {
            $data = $this->data;
        }

        $json = json_encode([
            'tags'   => $this->getTags(),
            'data'   => $data,
            'time'   => $this->getTime(),
            'max_id' => $this->getMaxId(),
            'min_id' => $this->getMinId(),
            'id_tag' => $this->getIdTag(),
        ], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new CacheException('Не удалось сериализовать данные для кэширования');
        }

        return (string)$json;
    }
}
