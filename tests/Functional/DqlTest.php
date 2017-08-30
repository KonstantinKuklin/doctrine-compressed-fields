<?php

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Tests\Functional;

use KonstantinKuklin\DoctrineCompressedFields\Tests\Stub\TestEntity;

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */
class DqlTest extends Common
{
    public function setUp()
    {
        $this->connectSqLite();
        $this->clearTableTest();
    }

    public function testUpdateWithDelete()
    {
        $this->sqLite->query('INSERT INTO test (id, compressed1) VALUES (1, 36), (2, 37), (3, 3)');
        $Query = $this->EntityManager->createQuery('SELECT t FROM ' . TestEntity::class . ' t WHERE BITS_MASK(t.type, 1) = 1');
        $result = $Query->getArrayResult();
        self::assertEquals(
            [
                [
                    'id' => 1,
                    'compressed1' => 36,
                ],
                [
                    'id' => 2,
                    'compressed1' => 37,
                ],
            ],
            $result
        );
    }
}
