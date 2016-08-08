<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeExtractToEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the "before extract to" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\BeforeExtractToEvent
 */
class BeforeExtractToEventTest extends TestCase
{
    /**
     * The directory path.
     *
     * @var string
     */
    private $dir = '/path/to/dir';

    /**
     * The event manager
     *
     * @var BeforeExtractToEvent
     */
    private $event;

    /**
     * The overwrite flag.
     *
     * @var boolean
     */
    private $overwrite = true;

    /**
     * The paths to extract.
     *
     * @var string[]
     */
    private $paths = ['path/to/a.php', 'path/b.php'];

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that the directory path is manageable.
     */
    public function testSetAndRetrieveDirectoryPath()
    {
        self::assertEquals(
            $this->dir,
            $this->event->getDir(),
            'The directory path was not returned.'
        );

        $dir = 'test';

        $this->event->setDir($dir);

        self::assertEquals(
            $dir,
            $this->event->getDir(),
            'The directory path was not set.'
        );
    }

    /**
     * Verify that the paths to extract is manageable.
     */
    public function testSetAndRetrievePathsToExtract()
    {
        self::assertEquals(
            $this->paths,
            $this->event->getPaths(),
            'The paths to extract were not returned.'
        );

        $paths = ['test.php'];

        $this->event->setPaths($paths);

        self::assertEquals(
            $paths,
            $this->event->getPaths(),
            'The paths to extract were not set.'
        );
    }

    /**
     * Verify that the overwrite flag is manageable.
     */
    public function testSetAndRetrieveOverwriteFlag()
    {
        self::assertEquals(
            $this->overwrite,
            $this->event->isOverwrite(),
            'The overwrite flag was not returned.'
        );

        $overwrite = false;

        $this->event->setOverwrite($overwrite);

        self::assertEquals(
            $overwrite,
            $this->event->isOverwrite(),
            'The overwrite flag was not set.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new BeforeExtractToEvent(
            $this->sqon,
            $this->dir,
            $this->paths,
            $this->overwrite
        );
    }
}
