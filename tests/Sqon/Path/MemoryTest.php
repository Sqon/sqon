<?php

namespace Test\Sqon\Container;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Path\Memory;

/**
 * Verifies that the in memory path manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Path\Memory
 */
class MemoryTest extends TestCase
{
    /**
     * The compression mode.
     *
     * @var integer
     */
    private $compression = Memory::GZIP;

    /**
     * The file contents.
     *
     * @var null|string
     */
    private $contents = 'test';

    /**
     * The last modified Unix timestamp.
     *
     * @var integer
     */
    private $modified = 123;

    /**
     * The Unix file permissions.
     *
     * @var integer
     */
    private $permissions = 456;

    /**
     * The in memory path manager.
     *
     * @var Memory
     */
    private $path;

    /**
     * The type of the path.
     *
     * @var integer
     */
    private $type = Memory::FILE;

    /**
     * Verify that the compression mode can be retrieved.
     */
    public function testRetrieveTheCompressionMode()
    {
        self::assertEquals(
            $this->compression,
            $this->path->getCompression(),
            'The compression mode was not returned.'
        );
    }

    /**
     * Verify that the file contents can be retrieved.
     */
    public function testRetrieveTheFileContents()
    {
        self::assertEquals(
            $this->contents,
            $this->path->getContents(),
            'The file contents were not returned.'
        );
    }

    /**
     * Verify that the last modified Unix timestamp can be retrieved.
     */
    public function testRetrieveTheLastModifiedUnixTimestamp()
    {
        self::assertEquals(
            $this->modified,
            $this->path->getModified(),
            'The last modified Unix timestamp was not returned.'
        );
    }

    /**
     * Verify that the Unix file permissions can be retrieved.
     */
    public function testRetrieveTheUnixFilePermissions()
    {
        self::assertEquals(
            $this->permissions,
            $this->path->getPermissions(),
            'The Unix file permissions were not returned.'
        );
    }

    /**
     * Verify that the type of the path can be retrieved.
     */
    public function testRetrieveThePathType()
    {
        self::assertEquals(
            $this->type,
            $this->path->getType(),
            'The type of the path was not returned.'
        );
    }

    /**
     * Creates a new database path manager.
     */
    public function setUp()
    {
        $this->path = new Memory(
            $this->contents,
            $this->type,
            $this->compression,
            $this->modified,
            $this->permissions
        );
    }
}
