<?php

namespace Test\Sqon\Path;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Path\File;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the file path manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Path\File
 */
class FileTest extends TestCase
{
    use TempTrait;

    /**
     * The file path.
     *
     * @var string
     */
    private $file;

    /**
     * The file path manager.
     *
     * @var File
     */
    private $manager;

    /**
     * Verify that the file contents can be retrieved.
     */
    public function testRetrieveTheFileContents()
    {
        file_put_contents($this->file, 'test');

        self::assertEquals(
            file_get_contents($this->file),
            $this->manager->getContents(),
            'The file contents were not returned.'
        );

        $manager = new File(__DIR__);

        self::assertNull(
            $manager->getContents(),
            'No contents should be returned for a directory.'
        );
    }

    /**
     * Verify that the last modified Unix timestamp can be retrieved.
     */
    public function testRetrieveTheLastModifiedUnixTimestamp()
    {
        self::assertEquals(
            filemtime($this->file),
            $this->manager->getModified(),
            'The last modified Unix timestamp was not returned.'
        );
    }

    /**
     * Verify that the Unix file permissions can be retrieved.
     */
    public function testRetrieveTheUnixFilePermissions()
    {
        self::assertEquals(
            fileperms($this->file),
            $this->manager->getPermissions(),
            'The Unix file permissions were not returned.'
        );
    }

    /**
     * Verify that the type of the path can be retrieved.
     */
    public function testRetrieveThePathType()
    {
        self::assertEquals(
            File::FILE,
            $this->manager->getType(),
            'The type of the path was not returned.'
        );

        $manager = new File(__DIR__);

        self::assertEquals(
            File::DIRECTORY,
            $manager->getType(),
            'The correct type of the path was not returned.'
        );
    }

    /**
     * Creates a new file path manager.
     */
    protected function setUp()
    {
        $this->file = $this->createTemporaryFile();

        clearstatcache(true, $this->file);

        $this->manager = new File($this->file);
    }
}
