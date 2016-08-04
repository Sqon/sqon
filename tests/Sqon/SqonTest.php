<?php

namespace Test\Sqon;

use ArrayIterator;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Path\Memory;
use Sqon\Sqon;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the Sqon manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Sqon
 */
class SqonTest extends TestCase
{
    use TempTrait;

    /**
     * The path to the Sqon file.
     *
     * @var string
     */
    private $file;

    /**
     * The new Sqon.
     *
     * @var Sqon
     */
    private $sqon;

    /**
     * Verify that the Sqon can be committed to disk.
     */
    public function testCommitChangesToDisk()
    {
        self::assertFileNotExists(
            $this->file,
            'The Sqon should exist yet.'
        );

        $this->sqon->commit();

        self::assertFileExists(
            $this->file,
            'The Sqon should have been created.'
        );
    }

    /**
     * Verify that a new Sqon can be created.
     */
    public function testCreateANewSqon()
    {
        $script = '<?php __HALT_COMPILER();';

        $sqon = Sqon::create(
            $this->createTemporaryFile(),
            $script
        );

        self::assertInstanceOf(
            Sqon::class,
            $sqon,
            'A new Sqon manager was not returned.'
        );

        self::assertEquals(
            $script,
            $sqon->getBootstrap(),
            'The PHP bootstrap script was not properly set.'
        );
    }

    /**
     * Verify that a new PHP bootstrap script can be created.
     */
    public function testCreateNewPHPBootstrapScript()
    {
        self::assertEquals(
            trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            Sqon::createBootstrap(),
            'The PHP bootstrap script was not created.'
        );

        self::assertEquals(
            "#!/usr/bin/env php\n" . trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            Sqon::createBootstrap('#!/usr/bin/env php'),
            'The PHP bootstrap script was not created with the shebang line.'
        );
    }

    /**
     * Verify that the PHP bootstrap script can be retrieved.
     */
    public function testRetrieveCurrentPhpBootstrapScript()
    {
        self::assertEquals(
            trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            $this->sqon->getBootstrap(),
            'The PHP bootstrap script was not returned.'
        );
    }

    /**
     * Verify that the contents of a Sqon can be extracted.
     */
    public function testExtractSqonContents()
    {
        $dir = $this->createTemporaryDirectory();
        $time = time() - 10000;
        $perms = 0600;

        mkdir($dir . '/d/e', 0755, true);
        touch($dir . '/d/e/f', $old = time() - 40000);

        $a = new Memory('a', Memory::FILE, $time, $perms);
        $c = new Memory(null, Memory::DIRECTORY, $time, $perms);

        $this
            ->sqon
            ->setPath('a', $a)
            ->setPath('b/c', $c)
            ->setPath('d/e/f', new Memory(null))
            ->setPath('x/y/z', new Memory(null))
            ->extractTo(
                $dir,
                [
                    'a',
                    'b/c'
                ]
            )
        ;

        self::assertTrue(
            is_file($dir . '/a'),
            'The file should have been extracted.'
        );

        self::assertEquals(
            $time,
            filemtime($dir . '/a'),
            'The last modified time was not set.'
        );

        self::assertEquals(
            decoct($perms),
            substr(sprintf('%o', fileperms($dir . '/a')), -3, 3),
            'The file permissions were not set.'
        );

        self::assertTrue(
            is_dir($dir . '/b/c'),
            'The directory should have been extracted.'
        );

        self::assertEquals(
            $time,
            filemtime($dir . '/b/c'),
            'The last modified time was not set.'
        );

        self::assertEquals(
            decoct($perms),
            substr(sprintf('%o', fileperms($dir . '/b/c')), -3, 3),
            'The directory permissions were not set.'
        );

        self::assertEquals(
            $old,
            filemtime($dir . '/d/e/f'),
            'The original file should not have been overwritten.'
        );

        self::assertFileNotExists(
            $dir . '/x/y/z',
            'The excluded file should not have been extracted.'
        );
    }

    /**
     * Verify that a path can be manages in the Sqon.
     */
    public function testManagePathsInTheSqon()
    {
        $path = new Memory('test');

        self::assertFalse(
            $this->sqon->hasPath('test.php'),
            'The path should not exist.'
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->setPath('test.php', $path),
            'The path setter did not return a fluent interface.'
        );

        self::assertTrue(
            $this->sqon->hasPath('test.php'),
            'The path should exist.'
        );

        self::assertEquals(
            $path,
            $this->sqon->getPath('test.php'),
            'The same path information was not returned.'
        );

        self::assertEquals(
            ['test.php' => $path],
            iterator_to_array($this->sqon->getPaths()),
            'The paths were not returned.'
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->removePath('test.php'),
            'The path remover did not return a fluent interface.'
        );

        self::assertFalse(
            $this->sqon->hasPath('test.php'),
            'The path was not removed.'
        );
    }

    /**
     * Verify that the compression mode can be set.
     */
    public function testSetDatabaseCompressionMode()
    {
        self::assertSame(
            $this->sqon,
            $this->sqon->setCompression(Sqon::GZIP),
            'The compression mode setter did not return a fluent interface.'
        );
    }

    /**
     * Verify that the signature for a Sqon can be verified.
     */
    public function testVerifySignatureForExistingSqon()
    {
        $this->sqon->commit();

        self::assertTrue(
            Sqon::isValid($this->file),
            'The signature should be valid.'
        );

        file_put_contents($this->file, 'x', FILE_APPEND);

        self::assertFalse(
            Sqon::isValid($this->file),
            'The signature should not be valid.'
        );
    }

    /**
     * Verify that an existing Sqon can be opened.
     */
    public function testExistingSqonCanBeOpened()
    {
        $script = '<?php __HALT_COMPILER();';

        $this->sqon->setBootstrap($script)->commit();

        $this->sqon = null;

        $sqon = Sqon::open($this->file);

        self::assertEquals(
            $script,
            $sqon->getBootstrap(),
            'The existing Sqon was not opened.'
        );
    }

    /**
     * Verify that paths can be set in the Sqon using an iterator.
     */
    public function testAddPathsUsingAnIterator()
    {
        $path = new Memory('test');
        $iterator = new ArrayIterator(
            [
                '.\\dir\\to\\..\\test.php' => $path
            ]
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->setPathsUsingIterator($iterator),
            'The iterator setter did not return a fluent interface.'
        );

        self::assertEquals(
            $path,
            $this->sqon->getPath('dir/test.php'),
            'The path from the iterator was not added.'
        );
    }

    /**
     * Creates a new Sqon.
     */
    protected function setUp()
    {
        $this->file = $this->createTemporaryFile();

        unlink($this->file);

        $this->sqon = Sqon::create($this->file);
    }
}
