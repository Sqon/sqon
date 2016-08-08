<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;

/**
 * Verifies that the "before set path" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\BeforeSetPathEvent
 */
class BeforeSetPathEventTest extends TestCase
{
    /**
     * The event manager.
     *
     * @var BeforeSetPathEvent
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
     * Verify that the path is manageable.
     */
    public function testSetAndRetrievePath()
    {
        self::assertEquals(
            $this->path,
            $this->event->getPath(),
            'The path was not returned.'
        );

        $path = 'alt.php';

        $this->event->setPath($path);

        self::assertEquals(
            $path,
            $this->event->getPath(),
            'The path was not changed.'
        );
    }

    /**
     * Verify that the path manager is manageable.
     */
    public function testSetAndRetrievePathManager()
    {
        self::assertSame(
            $this->manager,
            $this->event->getManager(),
            'The path manager was not returned.'
        );

        $manager = $this->getMockForAbstractClass(PathInterface::class);

        $this->event->setManager($manager);

        self::assertSame(
            $manager,
            $this->event->getManager(),
            'The path manager was not changed.'
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

        $this->event = new BeforeSetPathEvent(
            $this->sqon,
            $this->path,
            $this->manager
        );
    }
}
