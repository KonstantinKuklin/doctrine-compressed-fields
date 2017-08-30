<?php

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Tests\Unit;

use KonstantinKuklin\DoctrineCompressedFields\BitNormalizer;
use PHPUnit\Framework\TestCase;

class BitNormalizerTest extends TestCase
{
    public function dataCorrectWork()
    {
        return [
            [
                'input' => [1, "2-3", '10-13'],
                'expected' => [1, 2, 3, 10, 11, 12, 13],
            ],
        ];
    }

    /**
     * @dataProvider dataCorrectWork
     */
    public function testCorrectWork($input, $expected)
    {
        self::assertEquals($expected, BitNormalizer::getNormalized($input));
    }

    public function dataIncorrectInput()
    {
        return [
            [
                'input' => [1, "3-2"],
                'message' => 'Start bit zone should be greater than end. But got: 3 - 2',
            ],
            [
                'input' => [1, "3-"],
                'message' => "Incorrect Bit syntax: '3-'",
            ],
            [
                'input' => [1, "-4"],
                'message' => "Incorrect Bit syntax: '-4'",
            ],
            [
                'input' => [2, "1-4"],
                'message' => 'Bit 2 used more than once',
            ],
        ];
    }

    /**
     * @dataProvider dataIncorrectInput
     */
    public function testIncorrectInput($input, $message)
    {
        $this->expectExceptionMessage($message);

        BitNormalizer::getNormalized($input);
    }
}
