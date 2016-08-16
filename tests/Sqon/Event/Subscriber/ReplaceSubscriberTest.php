<?php

namespace Test\Sqon\Event\Subscriber;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\Subscriber\ReplaceSubscriber;
use Sqon\Path\Memory;
use Sqon\Path\PathInterface;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Verifies that the replacement subscriber functions as intended.
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
     * The path manager.
     *
     * @var MockObject|PathInterface
     */
    private $manager;

    /**
     * The Sqon manager mock.
     *
     * @var MockObject|SqonInterface
     */
    private $sqon;

    /**
     * Verify that directories are not processed.
     */
    public function testSubscriberDoesNotReplaceDirectories()
    {
        $this->dispatcher->addSubscriber(
            new ReplaceSubscriber(
                [
                    'files' => [
                        'replace.php' => [
                            '/Hello/' => 'Goodbye'
                        ]
                    ],
                    [
                        'global' => [
                            '/world/' => 'guest'
                        ]
                    ],
                    [
                        'regex' => [
                            '/\.php/' => [
                                '/\!/' => '.'
                            ]
                        ]
                    ]
                ]
            )
        );

        $manager = $this->getMockForAbstractClass(PathInterface::class);

        $manager
            ->expects($this->once())
            ->method('getType')
            ->willReturn(PathInterface::DIRECTORY)
        ;

        $event = $this
            ->getMockBuilder(BeforeSetPathEvent::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $event
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($manager)
        ;

        $event
            ->expects($this->never())
            ->method('setManager')
        ;

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);
    }

    /**
     * Verify that specific files are replaced.
     */
    public function testReplaceContentsForSpecificFiles()
    {
        $this->dispatcher->addSubscriber(
            new ReplaceSubscriber(
                [
                    'files' => [
                        'replace.php' => [
                            '/Hello/' => 'Goodbye'
                        ]
                    ]
                ]
            )
        );

        // Verify that contents are replaced.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'replace.php',
            new Memory('<?php echo "Hello, world!\n";')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            '<?php echo "Goodbye, world!\n";',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // Verify that unrelated content is not affected.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'test.txt',
            new Memory('This should not be replaced.')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'This should not be replaced.',
            $event->getManager()->getContents(),
            'The unrelated contents were replaced.'
        );
    }

    /**
     * Verify that all files are replaced.
     */
    public function testReplaceContentsForAllFiles()
    {
        $this->dispatcher->addSubscriber(
            new ReplaceSubscriber(
                [
                    'global' => [
                        '/world/' => 'guest'
                    ]
                ]
            )
        );

        // Verify that contents are replaced.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'alt.txt',
            new Memory('<?php echo "Hello, world!\n";')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            '<?php echo "Hello, guest!\n";',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // Verify that unrelated content is not affected.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'test.txt',
            new Memory('This should not be replaced.')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'This should not be replaced.',
            $event->getManager()->getContents(),
            'The unrelated contents were replaced.'
        );
    }

    /**
     * Verify that matching files are replaced.
     */
    public function testReplaceContentsForMatchingFiles()
    {
        $this->dispatcher->addSubscriber(
            new ReplaceSubscriber(
                [
                    'regex' => [
                        '/\.php/' => [
                            '/\!/' => '.'
                        ]
                    ]
                ]
            )
        );

        // Verify that contents are replaced.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'alt.php',
            new Memory('<?php echo "Hello, world!\n";')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            '<?php echo "Hello, world.\n";',
            $event->getManager()->getContents(),
            'The contents were not replaced.'
        );

        // Verify that unrelated content is not affected.
        $event = new BeforeSetPathEvent(
            $this->sqon,
            'test.txt',
            new Memory('This should not be replaced.')
        );

        $this->dispatcher->dispatch(BeforeSetPathEvent::NAME, $event);

        self::assertEquals(
            'This should not be replaced.',
            $event->getManager()->getContents(),
            'The unrelated contents were replaced.'
        );
    }

    /**
     * Creates a new event dispatcher.
     */
    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->manager = $this->getMockForAbstractClass(PathInterface::class);
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);
    }
}
