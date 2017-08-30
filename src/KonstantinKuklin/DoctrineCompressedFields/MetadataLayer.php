<?php

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields;

use ReflectionProperty;

class MetadataLayer
{
    const ANNOTATION = 'annotation';
    const REFLECTION = 'reflection';
    const PATH_MASK_BY_HUB = 'bits_mask_by_hub';
    const PATH_HUB = 'bits_hub';
    const PATH_HUB_MASK = 'hub_mask';

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string|ReflectionProperty                          $propertyName
     *
     * @return ReflectionProperty
     */
    public static function getHubReflection($classMetadata, $propertyName)
    {
        $result = self::getHub($classMetadata, $propertyName);

        return $result[self::REFLECTION];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string|ReflectionProperty                          $propertyName
     *
     * @return \KonstantinKuklin\DoctrineCompressedFields\Annotation\Hub
     */
    public static function getHubAnnotation($classMetadata, $propertyName)
    {
        $result = self::getHub($classMetadata, $propertyName);

        return $result[self::ANNOTATION];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string|ReflectionProperty                          $propertyName
     *
     * @return array
     */
    public static function getHub($classMetadata, $propertyName)
    {
        if ($propertyName instanceof ReflectionProperty) {
            $propertyName = $propertyName->getName();
        }
        $path = self::PATH_HUB;

        return $classMetadata->$path[$propertyName];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata        $classMetadata
     * @param \KonstantinKuklin\DoctrineCompressedFields\Annotation\Hub $annotation
     * @param ReflectionProperty                                        $reflection
     */
    public static function setHub($classMetadata, $annotation, $reflection)
    {
        $path = self::PATH_HUB;
        $reflection->setAccessible(true);

        $classMetadata->$path[$reflection->getName()] = [
            self::ANNOTATION => $annotation,
            self::REFLECTION => $reflection,
        ];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string|ReflectionProperty                          $propertyName
     *
     * @return \KonstantinKuklin\DoctrineCompressedFields\Annotation\Mask
     */
    public static function getMaskAnnotation($classMetadata, $propertyName)
    {
        if ($propertyName instanceof ReflectionProperty) {
            $propertyName = $propertyName->getName();
        }

        $path_hub_mask = self::PATH_HUB_MASK;
        $hubName = $classMetadata->$path_hub_mask[$propertyName];

        $path = self::PATH_MASK_BY_HUB;

        return $classMetadata->$path[$hubName][$propertyName][self::ANNOTATION];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string|ReflectionProperty                          $propertyName
     *
     * @return ReflectionProperty
     */
    public static function getMaskReflection($classMetadata, $propertyName)
    {
        if ($propertyName instanceof ReflectionProperty) {
            $propertyName = $propertyName->getName();
        }

        $path_hub_mask = self::PATH_HUB_MASK;
        $hubName = $classMetadata->$path_hub_mask[$propertyName];

        $path = self::PATH_MASK_BY_HUB;

        return $classMetadata->$path[$hubName][$propertyName][self::REFLECTION];
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param string                                             $hub
     *
     * @return array
     */
    public static function getMaskList($classMetadata, $hub)
    {
        $path = self::PATH_MASK_BY_HUB;

        return (!empty($classMetadata->$path[$hub]) ? $classMetadata->$path[$hub] : []);
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata         $classMetadata
     * @param \KonstantinKuklin\DoctrineCompressedFields\Annotation\Mask $annotation
     * @param ReflectionProperty                                         $reflection
     */
    public static function setMask($classMetadata, $annotation, $reflection)
    {
        $path = self::PATH_MASK_BY_HUB;
        $reflection->setAccessible(true);

        $classMetadata->$path[$annotation->property][$reflection->getName()] = [
            self::ANNOTATION => $annotation,
            self::REFLECTION => $reflection,
        ];

        $path_hub_mask = self::PATH_HUB_MASK;
        $classMetadata->$path_hub_mask[$reflection->getName()] = $annotation->property;
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     *
     * @return bool
     */
    public static function isAlreadyProcessed($classMetadata)
    {
        $path_hub = self::PATH_HUB;
        $path_mask = self::PATH_MASK_BY_HUB;

        return (!empty($classMetadata->$path_hub) || !empty($classMetadata->$path_mask));
    }
}
