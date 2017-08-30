<?php

/**
 * @author KonstantinKuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use KonstantinKuklin\DoctrineCompressedFields\Engine;
use KonstantinKuklin\DoctrineCompressedFields\MetadataLayer;

class LoadFlushListener implements EventSubscriber
{
    private $engine;

    /**
     * @var array
     */
    private $originalData;

    public function __construct()
    {
        $this->engine = new Engine();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preFlush,
            Events::postLoad,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        /** @var EntityManager $em */
        $em = $args->getObjectManager();
        $entityClass = get_class($entity);
        $classMetadata = $em->getClassMetadata($entityClass);

        $pathHub = MetadataLayer::PATH_HUB;
        $pathMaskGrouped = MetadataLayer::PATH_MASK_BY_HUB;

        $maskListGrouped = !empty($classMetadata->$pathMaskGrouped) ? $classMetadata->$pathMaskGrouped : [];
        $hubList = !empty($classMetadata->$pathHub) ? $classMetadata->$pathHub : [];

        $hash = implode(' ', (array)$classMetadata->getIdentifier());
        $identifierValueList = implode(' ', $classMetadata->getIdentifierValues($entity));
        foreach ($hubList as $hubData) {
            /** @var \ReflectionProperty $property */
            $property = $hubData['reflection'];
            $hubValue = $property->getValue($entity);

            $valueList = $this->engine->getUnpackedValueList(
                $hubValue,
                $maskListGrouped[$property->getName()]
            );

            foreach ($valueList as $valueKey => $value) {
                /** @var \ReflectionProperty $reflection */
                $reflection = $maskListGrouped[$property->getName()][$valueKey]['reflection'];
                if ($maskListGrouped[$property->getName()][$valueKey]['annotation']->type === Type::BOOLEAN) {
                    $value = (bool)$value;
                }
                $reflection->setValue($entity, $value);
                $this->originalData[$entityClass][$hash][$property->getName()] = [
                    'id' => $identifierValueList,
                    'value' => $value,
                ];
            }
        }
    }

    /**
     * @param PreFlushEventArgs $args
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function preFlush(PreFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->updateCompressedHubProperty($em, $entity);
        }

        if (empty($this->originalData)) {
            return;
        }

        foreach ($this->originalData as $classEntity => $hashList) {
            foreach ($hashList as $hash => $valueList) {
                $value = array_pop($valueList);
                $entity = $unitOfWork->tryGetByIdHash($value['id'], $classEntity);
                if (!$entity) {
                    continue;
                }

                $is_updated = $this->updateCompressedHubProperty($em, $entity);
                if ($is_updated && !$unitOfWork->isScheduledForUpdate($entity)) {
                    $unitOfWork->scheduleForUpdate($entity);
                }
            }
        }
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param                             $entity
     *
     * @return bool
     */
    private function updateCompressedHubProperty(EntityManager $em, $entity)
    {
        $entityClass = get_class($entity);
        $hash = spl_object_hash($entity);
        $classMetadata = $em->getClassMetadata($entityClass);

        $pathHub = MetadataLayer::PATH_HUB;
        $pathMaskGrouped = MetadataLayer::PATH_MASK_BY_HUB;

        $maskListGrouped = !empty($classMetadata->$pathMaskGrouped) ? $classMetadata->$pathMaskGrouped : [];
        $hubList = !empty($classMetadata->$pathHub) ? $classMetadata->$pathHub : [];

        $is_updated = false;
        foreach ($hubList as $hubData) {
            /** @var \ReflectionProperty $hubReflection */
            $hubReflection = $hubData['reflection'];

            $valueList = [];
            if (!isset($maskListGrouped[$hubReflection->getName()])) {
                continue;
            }

            foreach ($maskListGrouped[$hubReflection->getName()] as $maskData) {
                /** @var \ReflectionProperty $maskReflection */
                $maskReflection = $maskData['reflection'];
                $value = $maskReflection->getValue($entity);
                if (isset($this->originalData[$entityClass][$hash][$maskReflection->getName()])) {
                    if ($this->originalData[$entityClass][$hash][$maskReflection->getName()] == $value) {
                        // nothing was changed, we can skip it
                        continue;
                    }
                }
                if (!$value) {
                    $value = 0;
                }
                $valueList[$maskReflection->getName()] = $value;
            }

            if (empty($valueList)) {
                continue;
            }

            $oldHubValue = $hubReflection->getValue($entity);
            if (!$oldHubValue) {
                $oldHubValue = 0;
            }
            $newHubValue = $this->engine->getPackedValue(
                $oldHubValue,
                $maskListGrouped[$hubReflection->getName()],
                $valueList
            );
            if ($oldHubValue !== $newHubValue) {
                $hubReflection->setValue($entity, $newHubValue);
                $is_updated = true;
            }
        }

        return $is_updated;
    }
}
