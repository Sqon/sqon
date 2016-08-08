<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterExtractToEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the "after extract to" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\AfterExtractToEvent
 */
class AfterExtractToEventTest extends TestCase
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
     * Verify that the directory path is retrievable.
     */
    public function testRetrieveDirectoryPath()
    {
        self::assertEquals(
            $this->dir,
            $this->event->getDir(),
            'The directory path was not returned.'
        );
    }

    /**
     * Verify that the paths to extract is retrievable.
     */
    public function testRetrievePathsToExtract()
    {
        self::assertEquals(
            $this->paths,
            $this->event->getPaths(),
            'The paths to extract were not returned.'
        );
    }

    /**
     * Verify that the overwrite flag is retrievable.
     */
    public function testRetrieveOverwriteFlag()
    {
        self::assertEquals(
            $this->overwrite,
            $this->event->isOverwrite(),
            'The overwrite flag was not returned.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new AfterExtractToEvent(
            $this->sqon,
            $this->dir,
            $this->paths,
            $this->overwrite
        );
    }
}
