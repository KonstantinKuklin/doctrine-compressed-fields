<?php
/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class Mask
{
    /**
     * @Required
     * @var string
     */
    public $property;

    /**
     * @Required
     * @var string
     * @Enum({"integer", "boolean"})
     */
    public $type = 'integer';

    /**
     * @Required
     * @var array<string>
     */
    public $bits = [];
}
