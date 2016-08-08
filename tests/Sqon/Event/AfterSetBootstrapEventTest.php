<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterSetBootstrapEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the "after set bootstrap" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\AfterSetBootstrapEvent
 */
class AfterSetBootstrapEventTest extends TestCase
{
    /**
     * The event manager.
     *
     * @var AfterSetBootstrapEvent
     */
    private $event;

    /**
     * The PHP bootstrap script.
     *
     * @var string
     */
    private $script;

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that the PHP bootstrap script can be retrieved.
     */
    public function testRetrievePhpBootstrapScript()
    {
        self::assertEquals(
            $this->script,
            $this->event->getScript(),
            'The PHP bootstrap script was not returned.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->script = 'test';
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new AfterSetBootstrapEvent($this->sqon, $this->script);
    }
}
