<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AbstractEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the abstract event functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\AbstractEvent
 */
class AbstractEventTest extends TestCase
{
    /**
     * The abstract event manager.
     *
     * @var AbstractEvent|MockObject
     */
    private $event;

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that the Sqon manager can be retrieved.
     */
    public function testRetrieveTheSqonManager()
    {
        self::assertSame(
            $this->sqon,
            $this->event->getSqon(),
            'The Sqon manager was not returned.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);
        $this->event = $this->getMockForAbstractClass(
            AbstractEvent::class,
            [$this->sqon]
        );
    }
}
