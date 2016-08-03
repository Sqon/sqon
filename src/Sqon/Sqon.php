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
use Sqon\Exception\Container\DatabaseException;
use Sqon\Exception\SqonException;
use Sqon\Path\PathInterface;

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
     * The path to the Sqon.
     *
     * @var string
     */
    private $path;

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        (new Writer())->write(
            new File($this->path, 'w+'),
            new Memory($this->bootstrap, false),
            new File($this->databaseFile, 'r')
        );
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
    public function getBootstrap()
    {
        return $this->bootstrap;
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

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPath($path, PathInterface $manager)
    {
        $this->database->setPath($this->cleanPath($path), $manager);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPathsUsingIterator(Iterator $iterator)
    {
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
}
