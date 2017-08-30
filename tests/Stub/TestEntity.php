<?php
/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Tests\Stub;

use Doctrine\ORM\Mapping as ORM;
use KonstantinKuklin\DoctrineCompressedFields\Annotation as Bits;

/**
 * @ORM\Entity
 * @ORM\Table(name="test")
 */
class TestEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Bits\Hub(size=8)
     * @var int
     */
    private $compressed1;

    /**
     * @Bits\Mask(type="integer", property="compressed1", bits={"2-4", "6"})
     * @var int Can be a number from 0 to 15
     */
    private $type = 0;

    /**
     * @Bits\Mask(type="boolean", property="compressed1", bits={"0"})
     * @var bool
     */
    private $is_enabled;

    /**
     * @Bits\Mask(type="boolean", property="compressed1", bits={"5"})
     * @var bool
     */
    private $is_deleted;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setEnabled($is_enabled = true)
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param bool $is_deleted
     */
    public function setDeleted($is_deleted = true)
    {
        $this->is_deleted = $is_deleted;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
