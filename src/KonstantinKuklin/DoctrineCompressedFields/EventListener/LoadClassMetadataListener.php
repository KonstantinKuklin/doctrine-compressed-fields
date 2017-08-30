<?php

/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use KonstantinKuklin\DoctrineCompressedFields\Annotation\Hub;
use KonstantinKuklin\DoctrineCompressedFields\Annotation\Mask;
use KonstantinKuklin\DoctrineCompressedFields\BitNormalizer;
use KonstantinKuklin\DoctrineCompressedFields\MetadataLayer;

class LoadClassMetadataListener implements EventSubscriber
{
    /**
     * @var AnnotationReader
     */
    private static $defaultAnnotationReader;

    public function __construct()
    {
        $this->initDefaultAnnotationReader();
    }

    /**
     * Create default annotation reader for extension
     *
     * @throws \RuntimeException
     */
    private function initDefaultAnnotationReader()
    {
        if (null !== self::$defaultAnnotationReader) {
            return;
        }

        $docParser = new DocParser();
        $docParser->setImports([
            'Bits' => 'KonstantinKuklin\\DoctrineCompressedFields\\Annotation',
        ]);
        $docParser->setIgnoreNotImportedAnnotations(true);

        $reader = new AnnotationReader($docParser);

        AnnotationRegistry::registerFile(__DIR__ . '/../Annotation/Hub.php');
        AnnotationRegistry::registerFile(__DIR__ . '/../Annotation/Mask.php');
        $reader = new CachedReader($reader, new VoidCache());

        self::$defaultAnnotationReader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        if (MetadataLayer::isAlreadyProcessed($classMetadata)) {
            // we can skip it
            return;
        }

        $fieldNames = array_flip($classMetadata->getFieldNames());
        $reflectionClass = $classMetadata->getReflectionClass();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (isset($fieldNames[$reflectionProperty->getName()])) {
                $this->loadHubProperty($reflectionProperty, $classMetadata);
            } else {
                $this->loadMaskProperty($reflectionProperty, $classMetadata);
            }
        }
    }

    /**
     * @param \ReflectionProperty                                $reflectionProperty
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     *
     * @throws \Exception
     */
    private function loadMaskProperty($reflectionProperty, $classMetadata)
    {
        /** @var Mask $maskAnnotation */
        $maskAnnotation = self::$defaultAnnotationReader->getPropertyAnnotation(
            $reflectionProperty,
            Mask::class
        );

        if (!$maskAnnotation) {
            return;
        }

        // normalize bit list
        $maskAnnotation->bits = BitNormalizer::getNormalized(
            $maskAnnotation->bits
        );

        MetadataLayer::setMask($classMetadata, $maskAnnotation, $reflectionProperty);
    }

    /**
     * @param \ReflectionProperty                                $reflectionProperty
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     */
    private function loadHubProperty($reflectionProperty, $classMetadata)
    {
        $hubAnnotation = self::$defaultAnnotationReader->getPropertyAnnotation(
            $reflectionProperty,
            Hub::class
        );

        if (!$hubAnnotation) {
            return;
        }

        MetadataLayer::setHub($classMetadata, $hubAnnotation, $reflectionProperty);
    }
}
