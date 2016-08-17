<?php

namespace Test\Sqon\Event\Subscriber;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\Subscriber\FilterSubscriber;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Verifies that the path filtering subscriber functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\Subscriber\FilterSubscriber
 */
class FilterSubscriberTest extends TestCase
{
    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * The event subscriber.
     *
     * @var FilterSubscriber
     */
    private $subscriber;

    /**
     * Verify that a path is excluded by name.
     */
    public function testExcludeAPathByName()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->excludeByName('exclude.php'),
            'The method did not return a fluent interface.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Verify that a path is excluded by path.
     */
    public function testExcludeAPathByPath()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->excludeByPath('exclude/script.php'),
            'The method did not return a fluent interface.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude/script.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include/script.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Verify that a path is excluded by regular expression.
     */
    public function testExcludeAPathByPattern()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->excludeByPattern('/exclude/'),
            'The method did not return a fluent interface.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Verify that a path is only include by name.
     */
    public function testIncludeAPathByName()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->includeByName('include.php'),
            'The method did not return a fluent interface.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Verify that a path is only include by path.
     */
    public function testIncludeAPathByPath()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->includeByPath('include/'),
            'The method did not return a fluent interface.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include/script.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude/script.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Verify that a path is only include by matching a regular expression.
     */
    public function testIncludeAPathByPattern()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->includeByPattern('/include/'),
            'The method did not return a fluent interface.'
        );

        // The path should not be skipped.
        $event = $this->createEvent('include.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The path was not skipped.'
        );

        // The path should be skipped.
        $event = $this->createEvent('exclude.php');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not skipped.'
        );
    }

    /**
     * Creates a new event subscriber.
     */
    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->subscriber = new FilterSubscriber();

        $this->dispatcher->addSubscriber($this->subscriber);
    }

    /**
     * Creates a new event manager.
     *
     * @param string $path The path.
     */
    private function createEvent($path)
    {
        return new BeforeSetPathEvent(
            $this->getMockForAbstractClass(SqonInterface::class),
            $path,
            $this->getMockForAbstractClass(PathInterface::class)
        );
    }
}
