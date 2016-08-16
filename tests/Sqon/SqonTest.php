<?php

namespace Test\Sqon;

use ArrayIterator;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\AfterCommitEvent;
use Sqon\Event\AfterExtractToEvent;
use Sqon\Event\AfterSetPathEvent;
use Sqon\Event\AfterSetPathsUsingIteratorEvent;
use Sqon\Event\BeforeCommitEvent;
use Sqon\Event\BeforeExtractToEvent;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\BeforeSetPathsUsingIteratorEvent;
use Sqon\Path\Memory;
use Sqon\Sqon;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the Sqon manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Sqon
 */
class SqonTest extends TestCase
{
    use TempTrait;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * The invoked events.
     *
     * @var array
     */
    private $events = [
        AfterCommitEvent::NAME => false,
        BeforeCommitEvent::NAME => false,
    ];

    /**
     * The path to the Sqon file.
     *
     * @var string
     */
    private $file;

    /**
     * The new Sqon.
     *
     * @var Sqon
     */
    private $sqon;

    /**
     * Verify that the Sqon can be committed to disk.
     */
    public function testCommitChangesToDisk()
    {
        $this->eventDispatcher->addListener(
            BeforeCommitEvent::NAME,
            function () {
                $this->events[BeforeCommitEvent::NAME] = true;
            }
        );

        $this->eventDispatcher->addListener(
            AfterCommitEvent::NAME,
            function () {
                $this->events[AfterCommitEvent::NAME] = true;
            }
        );

        self::assertFileNotExists(
            $this->file,
            'The Sqon should exist yet.'
        );

        $this->sqon->commit();

        self::assertFileExists(
            $this->file,
            'The Sqon should have been created.'
        );

        self::assertTrue(
            $this->events[BeforeCommitEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                BeforeCommitEvent::NAME
            )
        );

        self::assertTrue(
            $this->events[AfterCommitEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                AfterCommitEvent::NAME
            )
        );
    }

    /**
     * Verify that a new Sqon can be created.
     */
    public function testCreateANewSqon()
    {
        $script = '<?php __HALT_COMPILER();';

        $sqon = Sqon::create(
            $this->createTemporaryFile(),
            $script
        );

        self::assertInstanceOf(
            Sqon::class,
            $sqon,
            'A new Sqon manager was not returned.'
        );

        self::assertEquals(
            $script,
            $sqon->getBootstrap(),
            'The PHP bootstrap script was not properly set.'
        );
    }

    /**
     * Verify that a new PHP bootstrap script can be created.
     */
    public function testCreateNewPHPBootstrapScript()
    {
        self::assertEquals(
            trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            Sqon::createBootstrap(),
            'The PHP bootstrap script was not created.'
        );

        self::assertEquals(
            "#!/usr/bin/env php\n" . trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            Sqon::createBootstrap('#!/usr/bin/env php'),
            'The PHP bootstrap script was not created with the shebang line.'
        );
    }

    /**
     * Verify that the PHP bootstrap script can be retrieved.
     */
    public function testRetrieveCurrentPhpBootstrapScript()
    {
        self::assertEquals(
            trim(file_get_contents(Sqon::BOOTSTRAP_FILE)),
            $this->sqon->getBootstrap(),
            'The PHP bootstrap script was not returned.'
        );
    }

    /**
     * Verify that the contents of a Sqon can be extracted.
     */
    public function testExtractSqonContents()
    {
        $this->eventDispatcher->addListener(
            BeforeExtractToEvent::NAME,
            function () {
                $this->events[BeforeExtractToEvent::NAME] = true;
            }
        );

        $this->eventDispatcher->addListener(
            AfterExtractToEvent::NAME,
            function () {
                $this->events[AfterExtractToEvent::NAME] = true;
            }
        );

        $dir = $this->createTemporaryDirectory();
        $time = time() - 10000;
        $perms = 0600;

        mkdir($dir . '/d/e', 0755, true);
        touch($dir . '/d/e/f', $old = time() - 40000);

        $a = new Memory('a', Memory::FILE, $time, $perms);
        $c = new Memory(null, Memory::DIRECTORY, $time, $perms);

        $this
            ->sqon
            ->setPath('a', $a)
            ->setPath('b/c', $c)
            ->setPath('d/e/f', new Memory(null))
            ->setPath('x/y/z', new Memory(null))
            ->extractTo(
                $dir,
                [
                    'a',
                    'b/c',
                    'd/e/f'
                ],
                false
            )
        ;

        self::assertTrue(
            $this->events[BeforeExtractToEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                BeforeExtractToEvent::NAME
            )
        );

        self::assertTrue(
            $this->events[AfterExtractToEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                AfterExtractToEvent::NAME
            )
        );

        self::assertTrue(
            is_file($dir . '/a'),
            'The file should have been extracted.'
        );

        self::assertEquals(
            $time,
            filemtime($dir . '/a'),
            'The last modified time was not set.'
        );

        self::assertEquals(
            decoct($perms),
            substr(sprintf('%o', fileperms($dir . '/a')), -3, 3),
            'The file permissions were not set.'
        );

        self::assertTrue(
            is_dir($dir . '/b/c'),
            'The directory should have been extracted.'
        );

        self::assertEquals(
            $time,
            filemtime($dir . '/b/c'),
            'The last modified time was not set.'
        );

        self::assertEquals(
            decoct($perms),
            substr(sprintf('%o', fileperms($dir . '/b/c')), -3, 3),
            'The directory permissions were not set.'
        );

        self::assertEquals(
            $old,
            filemtime($dir . '/d/e/f'),
            'The original file should not have been overwritten.'
        );

        self::assertFileNotExists(
            $dir . '/x/y/z',
            'The excluded file should not have been extracted.'
        );
    }

    public function testCountPathsInSqon()
    {
        self::assertEquals(
            0,
            count($this->sqon),
            'There should be no paths in the database.'
        );

        $this->sqon->setPath('a', new Memory());

        self::assertEquals(
            1,
            count($this->sqon),
            'There should be exactly one path in the database.'
        );
    }

    /**
     * Verify that a path can be manages in the Sqon.
     */
    public function testManagePathsInTheSqon()
    {
        $path = new Memory('test');

        self::assertFalse(
            $this->sqon->hasPath('test.php'),
            'The path should not exist.'
        );

        $this->eventDispatcher->addListener(
            BeforeSetPathEvent::NAME,
            function () {
                $this->events[BeforeSetPathEvent::NAME] = true;
            }
        );

        $this->eventDispatcher->addListener(
            AfterSetPathEvent::NAME,
            function () {
                $this->events[AfterSetPathEvent::NAME] = true;
            }
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->setPath('test.php', $path),
            'The path setter did not return a fluent interface.'
        );

        self::assertTrue(
            $this->events[BeforeSetPathEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                BeforeSetPathEvent::NAME
            )
        );

        self::assertTrue(
            $this->events[AfterSetPathEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                AfterSetPathEvent::NAME
            )
        );

        self::assertTrue(
            $this->sqon->hasPath('test.php'),
            'The path should exist.'
        );

        self::assertEquals(
            $path,
            $this->sqon->getPath('test.php'),
            'The same path information was not returned.'
        );

        self::assertEquals(
            ['test.php' => $path],
            iterator_to_array($this->sqon->getPaths()),
            'The paths were not returned.'
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->removePath('test.php'),
            'The path remover did not return a fluent interface.'
        );

        self::assertFalse(
            $this->sqon->hasPath('test.php'),
            'The path was not removed.'
        );
    }

    /**
     * Verify that an event can prevent a path from being set.
     */
    public function testEventPreventsAPathFromBeingSet()
    {
        $this->eventDispatcher->addListener(
            BeforeSetPathEvent::NAME,
            function (BeforeSetPathEvent $event) {
                $event->skip();
            }
        );

        $this->sqon->setPath('test.php', new Memory('test'));

        self::assertFalse(
            $this->sqon->hasPath('test.php'),
            'The event did not prevent a path from being set.'
        );
    }

    /**
     * Verify that the path to commit the Sqon to is returned.
     */
    public function testGetPathForTheSqon()
    {
        self::assertEquals(
            $this->file,
            $this->sqon->getPathToSqon(),
            'The path to the Sqon was not returned.'
        );
    }

    /**
     * Verify that the compression mode can be set.
     */
    public function testSetDatabaseCompressionMode()
    {
        self::assertSame(
            $this->sqon,
            $this->sqon->setCompression(Sqon::GZIP),
            'The compression mode setter did not return a fluent interface.'
        );
    }

    /**
     * Verify that the signature for a Sqon can be verified.
     */
    public function testVerifySignatureForExistingSqon()
    {
        $this->sqon->commit();

        self::assertTrue(
            Sqon::isValid($this->file),
            'The signature should be valid.'
        );

        file_put_contents($this->file, 'x', FILE_APPEND);

        self::assertFalse(
            Sqon::isValid($this->file),
            'The signature should not be valid.'
        );
    }

    /**
     * Verify that an existing Sqon can be opened.
     */
    public function testExistingSqonCanBeOpened()
    {
        $script = '<?php __HALT_COMPILER();';

        $this->sqon->setBootstrap($script)->commit();

        $this->sqon = null;

        $sqon = Sqon::open($this->file);

        self::assertEquals(
            $script,
            $sqon->getBootstrap(),
            'The existing Sqon was not opened.'
        );
    }

    /**
     * Verify that paths can be set in the Sqon using an iterator.
     */
    public function testAddPathsUsingAnIterator()
    {
        $this->eventDispatcher->addListener(
            BeforeSetPathsUsingIteratorEvent::NAME,
            function () {
                $this->events[BeforeSetPathsUsingIteratorEvent::NAME] = true;
            }
        );

        $this->eventDispatcher->addListener(
            AfterSetPathsUsingIteratorEvent::NAME,
            function () {
                $this->events[AfterSetPathsUsingIteratorEvent::NAME] = true;
            }
        );

        $path = new Memory('test');
        $iterator = new ArrayIterator(
            [
                '.\\dir\\to\\..\\test.php' => $path
            ]
        );

        self::assertSame(
            $this->sqon,
            $this->sqon->setPathsUsingIterator($iterator),
            'The iterator setter did not return a fluent interface.'
        );

        self::assertTrue(
            $this->events[BeforeSetPathsUsingIteratorEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                BeforeSetPathsUsingIteratorEvent::NAME
            )
        );

        self::assertTrue(
            $this->events[AfterSetPathsUsingIteratorEvent::NAME],
            sprintf(
                'The "%s" event was not dispatched.',
                AfterSetPathsUsingIteratorEvent::NAME
            )
        );

        self::assertEquals(
            $path,
            $this->sqon->getPath('dir/test.php'),
            'The path from the iterator was not added.'
        );
    }

    /**
     * Verify that the event dispatcher can be set and retrieved.
     */
    public function testSetAndRetrieveTheEventDispatcher()
    {
        $dispatcher = new EventDispatcher();

        self::assertSame(
            $this->sqon,
            $this->sqon->setEventDispatcher($dispatcher),
            'The event dispatcher setter did not return a fluent interface.'
        );

        self::assertSame(
            $dispatcher,
            $this->sqon->getEventDispatcher(),
            'The event dispatcher was not returned.'
        );
    }

    /**
     * Creates a new Sqon.
     */
    protected function setUp()
    {
        $this->eventDispatcher = new EventDispatcher();

        $this->file = $this->createTemporaryFile();

        unlink($this->file);

        $this->sqon = Sqon::create($this->file);
        $this->sqon->setEventDispatcher($this->eventDispatcher);
    }
}
