<?php

namespace Test\Sqon\Iterator;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Iterator\DirectoryIterator;
use Sqon\Path\File;

/**
 * Verifies that the recursive directory iterator functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Iterator\DirectoryIterator
 */
class DirectoryIteratorTest extends TestCase
{
    /**
     * The path to the directory.
     *
     * @var string
     */
    private $path;

    /**
     * Verify that iterator returns path managers for the directories and files.
     */
    public function testIteratorReturnsPathManagersForDirectoriesAndFiles()
    {
        $managers = [];

        foreach (new DirectoryIterator($this->path) as $path => $manager) {
            $managers[$path] = $manager;
        }

        self::assertEquals(
            [
                '/a' => new File($this->path . '/a'),
                '/sub' => new File($this->path . '/sub'),
                '/sub/b' => new File($this->path . '/sub/b')
            ],
            $managers,
            'The expected path managers were not returned.'
        );
    }

    /**
     * Verify that an alternative base path can be specified.
     */
    public function testUseAlternativeBasePathForSettingPaths()
    {
        $managers = [];
        $iterator = new DirectoryIterator($this->path, dirname($this->path));
        $dir = basename($this->path);

        foreach ($iterator as $path => $manager) {
            $managers[$path] = $manager;
        }

        self::assertEquals(
            [
                "/$dir/a" => new File($this->path . '/a'),
                "/$dir/sub" => new File($this->path . '/sub'),
                "/$dir/sub/b" => new File($this->path . '/sub/b')
            ],
            $managers,
            'The expected path managers were not returned.'
        );
    }

    /**
     * Creates a new test directory.
     */
    protected function setUp()
    {
        $this->path = tempnam(sys_get_temp_dir(), 'sqon-');

        unlink($this->path);
        mkdir($this->path . '/sub', 0755, true);
        file_put_contents($this->path . '/a', 'a');
        file_put_contents($this->path . '/sub/b', 'b');
    }

    /**
     * Destroys the test directory.
     */
    protected function tearDown()
    {
        unlink($this->path . '/a');
        unlink($this->path . '/sub/b');
        rmdir($this->path . '/sub');
        rmdir($this->path);
    }
}
