<?php

namespace Test\Sqon\Event\Subscriber;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterCommitEvent;
use Sqon\Event\Subscriber\ChmodSubscriber;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the chmod subscriber functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ChmodSubscriberTest extends TestCase
{
    use TempTrait;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $dispatcher;

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
     * Verifies that permissions are changed.
     */
    public function testChangePermissionsForCommittedSqon()
    {
        chmod($this->path, 0644);

        $this->dispatcher->addSubscriber(new ChmodSubscriber(0755));

        $this->dispatcher->dispatch(
            AfterCommitEvent::NAME,
            new AfterCommitEvent($this->sqon)
        );

        clearstatcache(true, $this->path);

        self::assertEquals(
            '755',
            substr(sprintf('%o', fileperms($this->path)), -3, 3),
            'The file permissions were not set.'
        );
    }

    /**
     * Creates a new event dispatcher.
     */
    protected function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->path = $this->createTemporaryFile();
        $this->sqon = $this->getMockForAbstractClass(SqonInterface::class);

        $this
            ->sqon
            ->expects($this->any())
            ->method('getPathToSqon')
            ->willReturn($this->path)
        ;
    }

    /**
     * Deletes the temporary paths.
     */
    protected function tearDown()
    {
        $this->deleteTemporaryFiles();
    }
}
