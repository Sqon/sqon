<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterSetPathEvent;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;

/**
 * Verifies that the "After set path" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\AfterSetPathEvent
 */
class AfterSetPathEventTest extends TestCase
{
    /**
     * The event manager.
     *
     * @var AfterSetPathEvent
     */
    private $event;

    /**
     * The path manager mock.
     *
     * @var MockObject|PathInterface
     */
    private $manager;

    /**
     * The path.
     *
     * @var string
     */
    private $path;

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that the path can be retrieved.
     */
    public function testSetAndRetrievePath()
    {
        self::assertEquals(
            $this->path,
            $this->event->getPath(),
            'The path was not returned.'
        );
    }

    /**
     * Verify that the path manager can be retrieved.
     */
    public function testSetAndRetrievePathManager()
    {
        self::assertSame(
            $this->manager,
            $this->event->getManager(),
            'The path manager was not returned.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->manager = $this->getMockForAbstractClass(PathInterface::class);
        $this->path = 'test.php';
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new AfterSetPathEvent(
            $this->sqon,
            $this->path,
            $this->manager
        );
    }
}
