<?php
/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class Hub
{
    /**
     * big int 8 byte    - 64 bit
     * int 4 byte        - 32 bit
     * medium int 3 byte - 24 bit
     * small int 2 byte  - 16 bit
     * tiny int 1 byte   - 8 bit
     *
     * @var int
     */
    public $size = 0;
}
