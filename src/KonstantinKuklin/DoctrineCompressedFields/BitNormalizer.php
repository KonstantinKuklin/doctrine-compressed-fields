<?php

/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields;

use KonstantinKuklin\DoctrineCompressedFields\Exception\WrongBitMappingRule;

class BitNormalizer
{
    /**
     * @param string[] $bitList
     *
     * @return int[]
     * @throws \Exception
     */
    public static function getNormalized(array $bitList)
    {
        $bitListNormalized = [];
        foreach ($bitList as $bitRow) {
            $matches = [];
            preg_match('/^(?<first>[\d]+)(-(?<second>[\d]+))?$/', (string)$bitRow, $matches);
            if (empty($matches)) {
                throw new WrongBitMappingRule("Incorrect Bit syntax: '{$bitRow}'");
            }

            if (!isset($matches['second'])) {
                if (isset($bitListNormalized[$matches['first']])) {
                    throw new WrongBitMappingRule('You can`t add "-" after number.');
                }
                $bitListNormalized[$matches['first']] = (int)$matches['first'];
                continue;
            }

            if ($matches['first'] > $matches['second']) {
                throw new WrongBitMappingRule("Start bit zone should be greater than end. But got: {$matches['first']} - {$matches['second']}");
            }
            if (!is_numeric($matches['first']) || !is_numeric($matches['second'])) {
                throw new WrongBitMappingRule("Start and end of zone should be numbers, but got: {$matches['first']} - {$matches['second']}");
            }

            for ($i = (int)$matches['first'], $end = $matches['second']; $i <= $end; $i++) {
                if (isset($bitListNormalized[$i])) {
                    throw new WrongBitMappingRule("Bit {$bitListNormalized[$i]} used more than once");
                }
                $bitListNormalized[$i] = $i; // Bits started from 1, but in array we start working with 0
            }
        }

        return array_values($bitListNormalized);
    }
}
