<?php

namespace Test\Sqon\Event\Subscriber;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\Subscriber\ReplaceSubscriber;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Verifies that the path replacement subscriber functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\Subscriber\ReplaceSubscriber
 */
class ReplaceSubscriberTest extends TestCase
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
     * Verify that directory paths are not modified.
     */
    public function testDoNotReplaceContentsForDirectoryPaths()
    {
        $this->subscriber->replaceByPath('test', '/pattern/', 'replacement');

        $manager = $this->getMockForAbstractClass(PathInterface::class);

        $manager
            ->expects(self::never())
            ->method('getContents')
        ;

        $manager
            ->expects(self::once())
            ->method('getType')
            ->willReturn(PathInterface::DIRECTORY)
        ;

        $event = new BeforeSetPathEvent(
            $this->getMockForAbstractClass(SqonInterface::class),
            'test',
            $manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);
    }

    /**
     * Verify that contents are replaced for all paths.
     */
    public function testReplaceContentsForAllPaths()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->replaceAll('/pattern/', 'replacement'),
            'The method did not return a fluent interface.'
        );

        // The contents should be replaced.
        $event = $this->createEvent('a.php', 'Test pattern.');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Test replacement.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // The contents should also be replaced.
        $event = $this->createEvent('b.php', 'Another test pattern.');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Another test replacement.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );
    }

    /**
     * Verify that contents are replaced for specific paths.
     */
    public function testReplaceContentsForASpecificPath()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->replaceByPath(
                'to/example.php',
                '/pattern/',
                'replacement'
            ),
            'The method did not return a fluent interface.'
        );

        // The contents should be replaced.
        $event = $this->createEvent('to/example.php', 'Test pattern.');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Test replacement.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // The contents should not be replaced.
        $event = $this->createEvent(
            'another/example.php',
            'Another test pattern.'
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Another test pattern.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );
    }

    /**
     * Verify that contents are replaced for matched paths.
     */
    public function testReplaceContentsForMatchedPaths()
    {
        self::assertSame(
            $this->subscriber,
            $this->subscriber->replaceByPattern(
                '/to/',
                '/pattern/',
                'replacement'
            ),
            'The method did not return a fluent interface.'
        );

        // The contents should be replaced.
        $event = $this->createEvent('to/example.php', 'Test pattern.');

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Test replacement.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // The contents should not be replaced.
        $event = $this->createEvent(
            'another/example.php',
            'Another test pattern.'
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'Another test pattern.',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );
    }

    /**
     * Creates a new event subscriber.
     */
    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->subscriber = new ReplaceSubscriber();

        $this->dispatcher->addSubscriber($this->subscriber);
    }

    /**
     * Creates a new event manager.
     *
     * @param string $path     The path.
     * @param string $contents The contents of the path.
     */
    private function createEvent($path, $contents)
    {
        $manager = $this->getMockForAbstractClass(PathInterface::class);
        $manager
            ->expects(self::atMost(1))
            ->method('getContents')
            ->willReturn($contents)
        ;

        return new BeforeSetPathEvent(
            $this->getMockForAbstractClass(SqonInterface::class),
            $path,
            $manager
        );
    }
}
