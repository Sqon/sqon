<?php

namespace Sqon;

use Exception;
use Iterator;
use KHerGe\File\File;
use KHerGe\File\Memory;
use PDO;
use Sqon\Container\Database;
use Sqon\Container\Reader;
use Sqon\Container\Signature;
use Sqon\Container\Writer;
use Sqon\Event\AfterCommitEvent;
use Sqon\Event\AfterExtractToEvent;
use Sqon\Event\AfterSetBootstrapEvent;
use Sqon\Event\AfterSetPathEvent;
use Sqon\Event\AfterSetPathsUsingIteratorEvent;
use Sqon\Event\BeforeCommitEvent;
use Sqon\Event\BeforeExtractToEvent;
use Sqon\Event\BeforeSetBootstrapEvent;
use Sqon\Event\BeforeSetPathEvent;
use Sqon\Event\BeforeSetPathsUsingIteratorEvent;
use Sqon\Event\SkipTrait;
use Sqon\Exception\Container\DatabaseException;
use Sqon\Exception\SqonException;
use Sqon\Path\PathInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages an individual Sqon.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Sqon implements SqonInterface
{
    /**
     * The path to the default PHP bootstrap script.
     *
     * @var string
     */
    const BOOTSTRAP_FILE = __DIR__ . '/../bootstrap.php';

    /**
     * The bootstrap script.
     *
     * @var string
     */
    private $bootstrap;

    /**
     * The database manager.
     *
     * @var Database
     */
    private $database;

    /**
     * The path to the database file.
     *
     * @var string
     */
    private $databaseFile;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * The path to the Sqon.
     *
     * @var string
     */
    private $path;

    /**
     * Delete the temporary database file.
     */
    public function __destruct()
    {
        $this->database = null;

        unlink($this->databaseFile);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->dispatch(BeforeCommitEvent::NAME, BeforeCommitEvent::class);

        (new Writer())->write(
            new File($this->path, 'w+'),
            new Memory($this->bootstrap, false),
            new File($this->databaseFile, 'r')
        );

        $this->dispatch(AfterCommitEvent::NAME, AfterCommitEvent::class);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->database->countPaths();
    }

    /**
     * {@inheritdoc}
     */
    public static function create($path, $bootstrap = null)
    {
        if (null === $bootstrap) {
            $bootstrap = self::createBootstrap();
        }

        $temp = tempnam(sys_get_temp_dir(), 'sqon-');

        if (!$temp) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                'A new temporary file could not be created.'
            );
            // @codeCoverageIgnoreEnd
        }

        $database = new Database(new PDO("sqlite:$temp"));
        $database->createSchema();

        return new self($path, $bootstrap, $temp, $database);
    }

    /**
     * {@inheritdoc}
     */
    public static function createBootstrap($shebang = null)
    {
        if (null === $shebang) {
            $shebang = '';
        } else {
            $shebang = trim($shebang) . "\n";
        }

        $script = trim((new File(self::BOOTSTRAP_FILE, 'r'))->read());

        return $shebang . $script;
    }

    /**
     * {@inheritdoc}
     */
    public function extractTo($dir, array $paths = [], $overwrite = true)
    {
        $this->dispatch(
            BeforeExtractToEvent::NAME,
            BeforeExtractToEvent::class,
            // @codeCoverageIgnoreStart
            function (BeforeExtractToEvent $event) use (
                &$dir,
                &$paths,
                &$overwrite
            ) {
            // @codeCoverageIgnoreEnd
                $dir = $event->getDir();
                $paths = $event->getPaths();
                $overwrite = $event->isOverwrite();
            },
            $dir,
            $paths,
            $overwrite
        );

        foreach ($this->database->getPaths() as $path => $manager) {
            if (!empty($paths) && !in_array($path, $paths)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $path;

            if (!$overwrite && file_exists($path)) {
                continue;
            }

            switch ($manager->getType()) {
                case PathInterface::DIRECTORY:
                    $this->extractDir($path, $manager);

                    break;

                case PathInterface::FILE:
                    $this->extractFile($path, $manager);

                    break;
            }
        }

        $this->dispatch(
            AfterExtractToEvent::NAME,
            AfterExtractToEvent::class,
            null,
            $dir,
            $paths,
            $overwrite
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath($path)
    {
        try {
            return $this->database->getPath($path);

        // @codeCoverageIgnoreStart
        } catch (DatabaseException $exception) {
            throw new SqonException(
                "The path information for \"$path\" could not be retrieved.",
                $exception
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    public function getPathToSqon()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        return $this->database->getPaths();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPath($path)
    {
        return $this->database->hasPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public static function isValid($path)
    {
        $file = new File($path, 'r');
        $reader = new Reader($file);
        $signature = new Signature();

        return $reader->getSignature() === $signature->generate($file, true);
    }

    /**
     * {@inheritdoc}
     */
    public static function open($path)
    {
        if (!self::isValid($path)) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The Sqon \"$path\" has an invalid signature."
            );
            // @codeCoverageIgnoreEnd
        }

        $temp = tempnam(sys_get_temp_dir(), 'sqon-');

        if (!$temp) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                'A new temporary file could not be created.'
            );
            // @codeCoverageIgnoreEnd
        }

        $reader = new Reader(new File($path, 'r'));
        $reader->getDatabase(new File($temp, 'w'));

        return new self(
            $path,
            $reader->getBootstrap(),
            $temp,
            new Database(new PDO("sqlite:$temp"))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function removePath($path)
    {
        $this->database->removePath($path);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setBootstrap($script)
    {
        $this->dispatch(
            BeforeSetBootstrapEvent::NAME,
            BeforeSetBootstrapEvent::class,
            function (BeforeSetBootstrapEvent $event) use (&$script) {
                $script = $event->getScript();
            },
            $script
        );

        if (!preg_match('{^(?:#![^\n\r]+[\n\r]+)?<\?php}', $script)) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                'The PHP bootstrap script does not begin with "<?php".'
            );
            // @codeCoverageIgnoreEnd
        }

        if ('__HALT_COMPILER();' !== substr($script, -18, 18)) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                'The PHP bootstrap script does not end with "__HALT_COMPILER()".'
            );
            // @codeCoverageIgnoreEnd
        }

        $this->bootstrap = $script;

        $this->dispatch(
            AfterSetBootstrapEvent::NAME,
            AfterSetBootstrapEvent::class,
            null,
            $script
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCompression($mode)
    {
        $this->database->setCompression($mode);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventDispatcher(
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->eventDispatcher = $dispatcher;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPath($path, PathInterface $manager)
    {
        $event = $this->dispatch(
            BeforeSetPathEvent::NAME,
            BeforeSetPathEvent::class,
            function (BeforeSetPathEvent $event) use (&$path, &$manager) {
                $manager = $event->getManager();
                $path = $event->getPath();
            },
            $path,
            $manager
        );

        if ((null !== $event) && $event->isSkipped()) {
            return $this;
        }

        $this->database->setPath($this->cleanPath($path), $manager);

        $this->dispatch(
            AfterSetPathEvent::NAME,
            AfterSetPathEvent::class,
            null,
            $path,
            $manager
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPathsUsingIterator(Iterator $iterator)
    {
        $this->dispatch(
            BeforeSetPathsUsingIteratorEvent::NAME,
            BeforeSetPathsUsingIteratorEvent::class,
            function (BeforeSetPathsUsingIteratorEvent $event) use (&$iterator) {
                $iterator = $event->getIterator();
            },
            $iterator
        );

        $this->database->begin();

        try {
            foreach ($iterator as $path => $manager) {
                if (!is_string($path)) {
                    // @codeCoverageIgnoreStart
                    throw new SqonException(
                        'The key returned by the iterator must be the path.'
                    );
                    // @codeCoverageIgnoreEnd
                }

                if (!($manager instanceof PathInterface)) {
                    // @codeCoverageIgnoreStart
                    throw new SqonException(
                        'The value returned by the iterator must be a path manager.'
                    );
                    // @codeCoverageIgnoreEnd
                }

                $this->setPath($this->cleanPath($path), $manager);
            }

        // @codeCoverageIgnoreStart
        } catch (Exception $exception) {
            $this->database->rollback();

            throw $exception;
        }
        // @codeCoverageIgnoreEnd

        $this->database->commit();

        $this->dispatch(
            AfterSetPathsUsingIteratorEvent::NAME,
            AfterSetPathsUsingIteratorEvent::class,
            null,
            $iterator
        );

        return $this;
    }

    /**
     * Initializes the new Sqon manager.
     *
     * @param string   $path         The path to the Sqon.
     * @param string   $bootstrap    The PHP bootstrap script.
     * @param string   $databaseFile The path to the database file.
     * @param Database $database     The database manager.
     */
    private function __construct(
        $path,
        $bootstrap,
        $databaseFile,
        Database $database
    ) {
        $this->database = $database;
        $this->databaseFile = $databaseFile;
        $this->path = $path;

        $this->setBootstrap($bootstrap);
    }

    /**
     * Cleans that path used inside the Sqon.
     *
     * @param string $path The original path.
     *
     * @return string The cleaned path.
     */
    private function cleanPath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        $path = explode('/', $path);

        foreach ($path as $i => $segment) {
            if ('..' === $segment) {
                unset($path[$i]);

                if (isset($path[$i - 1])) {
                    unset($path[$i - 1]);
                }
            } elseif ('.' === $segment) {
                unset($path[$i]);
            }
        }

        return join('/', $path);
    }

    /**
     * Dispatches an event if a dispatcher is registered.
     *
     * @param string        $name          The name of the event.
     * @param string        $class         The name of the event class.
     * @param callable|null $result        The event result handler.
     * @param mixed         $arguments,... The event constructor arguments.
     *
     * @return Event|null|SkipTrait The event manager.
     */
    private function dispatch(
        $name,
        $class,
        callable $result = null,
        // @codeCoverageIgnoreStart
        ...$arguments
    ) {
        // @codeCoverageIgnoreEnd
        if (null !== $this->eventDispatcher) {
            $event = new $class($this, ...$arguments);

            $this->eventDispatcher->dispatch($name, $event);

            if (null !== $result) {
                $result($event);
            }

            return $event;
        }

        return null;
    }

    /**
     * Extracts a directory path.
     *
     * @param string        $path        The path to create.
     * @param PathInterface $manager     The path manager.
     *
     * @throws SqonException If the directory could not be extracted.
     */
    private function extractDir($path, PathInterface $manager)
    {
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The directory \"$path\" could not be created."
            );
            // @codeCoverageIgnoreEnd
        }

        $this->setAttributes($path, $manager);
    }

    /**
     * Extracts a file path.
     *
     * @param string        $path        The path to create.
     * @param PathInterface $manager     The path manager.
     *
     * @throws SqonException If the file could not be extracted.
     */
    private function extractFile($path, PathInterface $manager)
    {
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The directory \"$dir\" could not be created."
            );
            // @codeCoverageIgnoreEnd
        }

        if (false === file_put_contents($path, $manager->getContents())) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The file \"$path\" could not be written."
            );
            // @codeCoverageIgnoreEnd
        }

        $this->setAttributes($path, $manager);
    }

    /**
     * Sets the attributes for an extracted path.
     *
     * @param string        $path    The path to modify.
     * @param PathInterface $manager The path manager.
     *
     * @throws SqonException If the attributes could not be set.
     */
    private function setAttributes($path, PathInterface $manager)
    {
        if (!chmod($path, $manager->getPermissions())) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The permissions could not be set for \"$path\"."
            );
            // @codeCoverageIgnoreEnd
        }

        if (!touch($path, $manager->getModified())) {
            // @codeCoverageIgnoreStart
            throw new SqonException(
                "The modified timestamp could not be set for \"$path\"."
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
