<?php
/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields;

use Doctrine\ORM\Mapping\ClassMetadata;

class Engine
{
    /**
     * @param int                                                          $hub
     * @param \KonstantinKuklin\DoctrineCompressedFields\Annotation\Mask[] $compressedList
     * @param int[]                                                        $valueList
     *
     * @return int
     * @throws \Exception
     */
    public function getPackedValue($hub, $compressedList, $valueList)
    {
        if (!$hub) {
            $hub = 0;
        }
        $hubBin = decbin($hub);

        foreach ($compressedList as $valueKey => $compressed) {
            $compressedAnnotation = $compressed[MetadataLayer::ANNOTATION];
            $bits = $compressedAnnotation->bits;
            $value = $valueList[$valueKey] ?: 0;
            if (!$value) {
                continue;
            }
            $valueBin = decbin($value);
            if (strlen($valueBin) > count($bits)) {
                throw new \Exception('bits are to few');
            }
            foreach ($bits as $valuePosition => $hubPosition) {
                $hubBin[$hubPosition] = isset($valueBin[$valuePosition]) ? $valueBin[$valuePosition] : '0';
            }
        }
        $hubBin = str_replace(' ', '0', $hubBin);
        $hubBinCorrectOrder = strrev($hubBin);

        return bindec($hubBinCorrectOrder);
    }

    /**
     * @param string $hub
     * @param array $compressedList
     *
     * @return array
     */
    public function getUnpackedValueList($hub, $compressedList)
    {
        if (!$hub) {
            return [];
        }
        $hubBin = strrev(decbin($hub));

        $valueList = [];
        foreach ($compressedList as $valueKey => $compressed) {
            $compressedAnnotation = $compressed[MetadataLayer::ANNOTATION];
            $bits = $compressedAnnotation->bits;
            $valueBin = '0';
            foreach ($bits as $valuePosition => $hubPosition) {
                $valueBin[$valuePosition] = !empty($hubBin[$hubPosition]) ? $hubBin[$hubPosition] : '0';
            }
            $valueBin = str_replace(' ', '0', $valueBin);
            $valueList[$valueKey] = bindec(strrev($valueBin));
        }

        return $valueList;
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $entityClassMetadata
     * @param string                              $hub
     *
     * @return \int[]
     * @throws \Exception
     */
    public function getFreeBits(ClassMetadata $entityClassMetadata, $hub)
    {
        $HubAnnotation = MetadataLayer::getHubAnnotation($entityClassMetadata, $hub);
        $bitsCount = $HubAnnotation->size;

        $maskList = MetadataLayer::getMaskList($entityClassMetadata, $hub);

        $bitsUsed = [];
        foreach ($maskList as $maskGrouped) {
            $MaskAnnotation = $maskGrouped[MetadataLayer::ANNOTATION];
            $bitsUsed = array_merge($bitsUsed, $MaskAnnotation->bits);
        }

        $bitsUsed = array_flip($bitsUsed);

        $bitsFree = [];
        for ($i = 0; $i < $bitsCount; $i++) {
            if (isset($bitsUsed[$i])) {
                continue;
            }
            $bitsFree[] = $i;
        }

        return $bitsFree;
    }
}
