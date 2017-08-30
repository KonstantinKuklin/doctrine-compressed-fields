<?php

/**
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */

namespace KonstantinKuklin\DoctrineCompressedFields\Tests\Functional;

use PHPUnit\Framework\TestCase;

class Common extends TestCase
{
    const TABLE_BITMASKS = 'bitmasks';

    protected $EntityManager;

    protected $dbPath;

    /** @var  \SQLite3 */
    protected $sqLite;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dbPath = tempnam(sys_get_temp_dir(), 'db');

        //        /** @var \Composer\Autoload\ClassLoader $loader */
        //        // First of all autoloading of vendors
        //        $loader = require __DIR__ . '/vendor/autoload.php';
        // ensure standard doctrine annotations are registered
        \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
            __DIR__ . '/../../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
        );

        // Second configure ORM
        // globally used cache driver, in production use APC or memcached
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $docParser = new \Doctrine\Common\Annotations\DocParser();
        //$docParser->setImports(['Compressed' => 'KonstantinKuklin\\DoctrineCompressedFields\\Annotation']);
        // standard annotation reader
        $annotationReader = new \Doctrine\Common\Annotations\AnnotationReader($docParser);
        $cachedAnnotationReader = new \Doctrine\Common\Annotations\CachedReader(
            $annotationReader, // use reader
            $cache // and a cache driver
        );
        // create a driver chain for metadata reading
        $driverChain = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        // now we want to register our application entities,
        // for that we need another metadata driver used for Entity namespace
        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
            $cachedAnnotationReader, // our cached annotation reader
            [__DIR__ . '../Stub'] // paths to look in
        );
        // NOTE: driver for application Entity can be different, Yaml, Xml or whatever
        // register annotation driver for our application Entity fully qualified namespace
        $driverChain->addDriver($annotationDriver, 'KonstantinKuklin\DoctrineCompressedFields\Tests\Stub');
        // general ORM configuration
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(false); // this can be based on production config.
        // register metadata driver
        $config->setMetadataDriverImpl($driverChain);
        // use our allready initialized cache driver
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->addCustomStringFunction(
            'BITS_MASK',
            \KonstantinKuklin\DoctrineCompressedFields\DqlFunction\BitsMask::class
        );
        // Third, create event manager and hook prefered extension listeners
        $evm = new \Doctrine\Common\EventManager();
        $listener = new \KonstantinKuklin\DoctrineCompressedFields\EventListener\LoadFlushListener();
        $evm->addEventSubscriber($listener);

        $loadClassMetadataListener = new \KonstantinKuklin\DoctrineCompressedFields\EventListener\LoadClassMetadataListener();
        $evm->addEventSubscriber($loadClassMetadataListener);

        // Finally, create entity manager
        $this->EntityManager = \Doctrine\ORM\EntityManager::create(
            [
                'driver' => 'pdo_sqlite',
                'path' => $this->dbPath,
            ],
            $config,
            $evm
        );
    }

    /**
     * @return \SQLite3
     */
    protected function connectSqLite()
    {
        return $this->sqLite = new \SQLite3($this->dbPath);
    }

    protected function clearDatabase()
    {
        $fp = fopen($this->dbPath, 'w+');
        fclose($fp);
    }

    protected function clearTableTest()
    {
        $query = file_get_contents(__DIR__ . '/../resources/test_table.sql');
        $this->sqLite->query($query);
    }
}
