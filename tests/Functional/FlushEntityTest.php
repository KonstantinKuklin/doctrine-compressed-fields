<?php

namespace KonstantinKuklin\DoctrineCompressedFields\Tests\Functional;

use KonstantinKuklin\DoctrineCompressedFields\Tests\Stub\TestEntity;

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */
class FlushEntityTest extends Common
{
    public function setUp()
    {
        $this->connectSqLite();
        $this->clearTableTest();
    }

    public function dataFlush()
    {
        return [
            [
                'expected' => ['id' => 1, 'compressed1' => 37],
                'type' => 1,
                'enabled' => true,
                'deleted' => true,
            ],
            [
                'expected' => ['id' => 1, 'compressed1' => 32],
                'type' => 0,
                'enabled' => false,
                'deleted' => true,
            ],
            [
                'expected' => ['id' => 1, 'compressed1' => 4],
                'type' => 2,
                'enabled' => false,
                'deleted' => false,
            ],
        ];
    }

    /**
     * @dataProvider dataFlush
     */
    public function testFlush($expected, $type, $enabled, $deleted)
    {
        $entity = new TestEntity();
        $entity->setType($type);
        $entity->setEnabled($enabled);
        $entity->setDeleted($deleted);

        $this->EntityManager->persist($entity);
        $this->EntityManager->flush($entity);
        $query = $this->sqLite->query('SELECT * FROM test');
        $result = $query->fetchArray(SQLITE3_ASSOC);
        self::assertEquals($expected, $result);
    }

    public function dataLoad()
    {
        return [
            [
                'insert' => '(1, 26), (2, 77), (3, 36)',
                'expected' => [
                    [
                        'id' => 1,
                        'type' => 6,
                        'enabled' => false,
                        'deleted' => false,
                    ],
                    [
                        'id' => 2,
                        'type' => 11,
                        'enabled' => true,
                        'deleted' => false,
                    ],
                    [
                        'id' => 3,
                        'type' => 1,
                        'enabled' => false,
                        'deleted' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataLoad
     */
    public function testLoad($insert, $expectedList)
    {
        $this->sqLite->query('INSERT INTO test (id, compressed1) VALUES ' . $insert);
        $Repository = $this->EntityManager->getRepository(TestEntity::class);
        $entityList = $Repository->findAll();

        self::assertCount(count($expectedList), $entityList);

        foreach ($entityList as $indx => $Entity) {
            $expected = $expectedList[$indx];
            $result = [];
            foreach ($expected as $key => $value) {
                $pathList = [
                    'is' . $key,
                    'get' . $key,
                ];

                foreach ($pathList as $method) {
                    if (!method_exists($Entity, $method)) {
                        continue;
                    }

                    $result[$key] = $Entity->$method();
                    break;
                }
            }

            self::assertEquals($expected, $result, 'Incorrect value for entity');
        }
    }

    public function testLoadAndUpdate()
    {
        $this->sqLite->query('INSERT INTO test (id, compressed1) VALUES (1, 36)');
        $Repository = $this->EntityManager->getRepository(TestEntity::class);
        /** @var \KonstantinKuklin\DoctrineCompressedFields\Tests\Stub\TestEntity $Entity */
        $Entity = $Repository->findOneBy(['id' => 1]);

        self::assertEquals(1, $Entity->getType());
        self::assertEquals(false, $Entity->isEnabled());
        self::assertEquals(true, $Entity->isDeleted());

        $Entity->setEnabled(true);
        $this->EntityManager->flush($Entity);
        $query = $this->sqLite->query('SELECT * FROM test');
        $result = $query->fetchArray(SQLITE3_ASSOC);
        self::assertEquals(
            ['id' => 1, 'compressed1' => 37],
            $result
        );
    }

    public function testUpdateWithDelete()
    {
        $this->sqLite->query('INSERT INTO test (id, compressed1) VALUES (1, 36)');
        $Repository = $this->EntityManager->getRepository(TestEntity::class);
        /** @var \KonstantinKuklin\DoctrineCompressedFields\Tests\Stub\TestEntity $Entity */
        $Entity = $Repository->findOneBy(['id' => 1]);
        $Entity->setEnabled(true);

        $this->EntityManager->remove($Entity);
        $this->EntityManager->flush();

        $query = $this->sqLite->query('SELECT * FROM test');
        $result = $query->fetchArray(SQLITE3_ASSOC);
        self::assertEquals(false, $result);
    }

    public function testFreeBits()
    {
        $ClassMetadataTestEntity = $this->EntityManager->getClassMetadata(TestEntity::class);
        $Engine = new \KonstantinKuklin\DoctrineCompressedFields\Engine();
        $free_bits = $Engine->getFreeBits($ClassMetadataTestEntity, 'compressed1');
        self::assertEquals([1, 7], $free_bits);
    }
}
