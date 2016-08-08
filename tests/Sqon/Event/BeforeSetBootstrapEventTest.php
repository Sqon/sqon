<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetBootstrapEvent;
use Sqon\SqonInterface;

/**
 * Verifies that the "before set bootstrap" event manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\BeforeSetBootstrapEvent
 */
class BeforeSetBootstrapEventTest extends TestCase
{
    /**
     * The event manager.
     *
     * @var BeforeSetBootstrapEvent
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
     * Verify that the PHP bootstrap script is manageable.
     */
    public function testSetAndRetrievePhpBootstrapScript()
    {
        self::assertEquals(
            $this->script,
            $this->event->getScript(),
            'The PHP bootstrap script was not returned.'
        );

        $script = 'alt';

        $this->event->setScript($script);

        self::assertEquals(
            $script,
            $this->event->getScript(),
            'The PHP bootstrap script was not changed.'
        );
    }

    /**
     * Creates a new event manager.
     */
    protected function setUp()
    {
        $this->script = 'test';
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this->event = new BeforeSetBootstrapEvent($this->sqon, $this->script);
    }
}
