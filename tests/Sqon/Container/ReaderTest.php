<?php

namespace Test\Sqon\Container;

use KHerGe\File\File;
use PDO;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Container\Reader;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the reader functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Container\Reader
 */
class ReaderTest extends TestCase
{
    use TempTrait;

    /**
     * The path to the PHP bootstrap script.
     *
     * @var string
     */
    private $script = __DIR__ . '/../../../src/bootstrap.php';

    /**
     * Verify that the PHP bootstrap script can be read.
     */
    public function testReadPhpBootstrapScriptFromSqon()
    {
        $reader = new Reader(new File($this->script, 'r'));

        self::assertEquals(
            trim(file_get_contents($this->script)),
            $reader->getBootstrap(),
            'The PHP bootstrap script was not read properly from the Sqon.'
        );
    }

    /**
     * Verify that the embedded database can be extracted.
     */
    public function testExtractEmbeddedDatabaseFromSqon()
    {
        $path = $this->createTemporaryFile();

        file_put_contents($path, trim(file_get_contents($this->script)));
        file_put_contents($path, 'database', FILE_APPEND);
        file_put_contents($path, sha1_file($path, true), FILE_APPEND);

        $database = new File('php://memory', 'w+');
        $reader = new Reader(new File($path, 'r'));

        $reader->getDatabase($database);

        $database->seek(0);

        self::assertEquals(
            'database',
            $database->read(),
            'The embedded database was not extracted properly.'
        );
    }

    /**
     * Verify that the signature can be read.
     */
    public function testReadFileSignatureFromSqon()
    {
        $file = new File('php://memory', 'w+');
        $file->write(hash('sha1', 'test', true));
        $file->seek(0);

        $reader = new Reader($file);

        self::assertEquals(
            hash('sha1', 'test', true),
            $reader->getSignature(),
            'The signature was not properly read from the Sqon.'
        );
    }
}
