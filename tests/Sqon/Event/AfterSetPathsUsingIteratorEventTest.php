<?php

namespace Test\Sqon\Event;

use Iterator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterSetPathsUsingIteratorEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the "after set paths" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\AfterSetPathsUsingIteratorEvent
 */
class AfterSetPathsUsingIteratorEventTest extends TestCase
{
    /**
     * The event manager.
     *
     * @var AfterSetPathsUsingIteratorEvent
     */
    private $event;

    /**
     * The paths iterator mock.
     *
     * @var Iterator|MockObject
     */
    private $iterator;

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that the iterator can be retrieved.
     */
    public function testSetAndRetrievePathsIterator()
    {
        self::assertSame(
            $this->iterator,
            $this->event->getIterator(),
            'The paths iterator was not returned.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->iterator = $this->getMockForAbstractClass(Iterator::class);
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new AfterSetPathsUsingIteratorEvent(
            $this->sqon,
            $this->iterator
        );
    }
}
