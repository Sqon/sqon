<?php

namespace Test\Sqon\Event\Subscriber;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\Subscriber\FilterSubscriber;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the path filtering subscriber functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\Subscriber\FilterSubscriber
 */
class FilterSubscriberTest extends TestCase
{
    use TempTrait;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * The path manager mock.
     *
     * @var MockObject|PathInterface
     */
    private $manager;

    /**
     * The path to the Sqon.
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
     * Verify that a path can be excluded by name.
     */
    public function testExcludePathByName()
    {
        $this->dispatcher->addSubscriber(
            new FilterSubscriber(
                [
                    'exclude' => ['name' => ['exclude.php']]
                ]
            )
        );

        // Check that the filter works.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'exclude.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not filtered.'
        );

        // Check that the filter does not block good paths.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'include.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The unaffected path was skipped.'
        );
    }

    /**
     * Verify that a path can be excluded by regular expression.
     */
    public function testExcludePathByRegularExpression()
    {
        $this->dispatcher->addSubscriber(
            new FilterSubscriber(
                [
                    'exclude' => ['regex' => ['/exclude/']]
                ]
            )
        );

        // Check that the filter works.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'exclude.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not filtered.'
        );

        // Check that the filter does not block good paths.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'include.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The unaffected path was skipped.'
        );
    }

    /**
     * Verify that a path can be included by name.
     */
    public function testIncludePathByName()
    {
        $this->dispatcher->addSubscriber(
            new FilterSubscriber(
                [
                    'include' => ['name' => ['include.php']]
                ]
            )
        );

        // Check that the filter works.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'exclude.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not filtered.'
        );

        // Check that the filter does not block good paths.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'include.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The unaffected path was skipped.'
        );
    }

    /**
     * Verify that a path can be excluded by regular expression.
     */
    public function testIncludePathByRegularExpression()
    {
        $this->dispatcher->addSubscriber(
            new FilterSubscriber(
                [
                    'include' => ['regex' => ['/include/']]
                ]
            )
        );

        // Check that the filter works.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'exclude.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertTrue(
            $event->isSkipped(),
            'The path was not filtered.'
        );

        // Check that the filter does not block good paths.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'include.php',
            $this->manager
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertFalse(
            $event->isSkipped(),
            'The unaffected path was skipped.'
        );
    }

    /**
     * Creates a new event dispatcher.
     */
    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->manager = $this->getMockForAbstractClass(PathInterface::class);
        $this->path = $this->createTemporaryFile();
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);
    }

    /**
     * Deletes the temporary paths.
     */
    protected function tearDown()
    {
        $this->deleteTemporaryFiles();
    }
}
