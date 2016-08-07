<?php

namespace Sqon;

use Countable;
use Generator;
use Iterator;
use Sqon\Container\Database;
use Sqon\Exception\SqonException;
use Sqon\Path\PathInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the public interface for a Sqon manager.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
interface SqonInterface extends Countable
{
    /**
     * Indicates that the contents are compressed using bzip2.
     *
     * @var integer
     */
    const BZIP2 = Database::BZIP2;

    /**
     * Indicates that the contents are compressed using gzip.
     *
     * @var integer
     */
    const GZIP = Database::GZIP;

    /**
     * Indicates that the contents are not compressed.
     *
     * @var integer
     */
    const NONE = Database::NONE;

    /**
     * The path to the primary script in the Sqon.
     *
     * @var string
     */
    const PRIMARY = '.sqon/primary.php';

    /**
     * Commits the changes to disk.
     *
     * Changes made to a Sqon are not performed directly on the Sqon. To save
     * any changes that are made to a new or existing Sqon, they have to be
     * committed to disk. If an existing Sqon is being committed, the contents
     * of the existing Sqon is replaced.
     *
     * ```php
     * $sqon->commit();
     * ```
     *
     * Observable events:
     *
     * - `CommitEvent::BEFORE`
     * - `CommitEvent::AFTER`
     */
    public function commit();

    /**
     * Returns the total number of paths in the Sqon.
     *
     * ```php
     * $count = count($sqon);
     * ```
     *
     * @return integer The total number of paths.
     */
    public function count();

    /**
     * Creates a new Sqon.
     *
     * If a file exists at the given path, the contents will be replaced when
     * the `commit()` method is called. Any existing path information in the
     * Sqon will be lost if the new Sqon is committed to disk.
     *
     * ```php
     * $sqon = Sqon::create('/path/to/example.sqon');
     * ```
     *
     * If a `$bootstrap` script is not provided, the default PHP bootstrap
     * script provided by the implementation will be used. This is the same
     * as providing the value returned by the `createBootstrap()` method
     * without any arguments.
     *
     * @param string      $path      The path to the Sqon.
     * @param null|string $bootstrap The PHP bootstrap script.
     *
     * @return SqonInterface The new Sqon manager.
     */
    public static function create($path, $bootstrap = null);

    /**
     * Creates a new PHP bootstrap script.
     *
     * The implementation of this interface will provide its own PHP bootstrap
     * script that is used to create new Sqons. By default, the script will not
     * have a shebang line. If a shebang line is provided, it is prepended to
     * the PHP bootstrap script that is returned.
     *
     * ```php
     * $bootstrap = Sqon::createBootstrap('#!/usr/bin/env php');
     * ```
     *
     * The PHP bootstrap script always ends with `__HALT_COMPILER();`.
     *
     * @param null|string $shebang The shebang line.
     *
     * @return string The new PHP bootstrap script.
     */
    public static function createBootstrap($shebang = null);

    /**
     * Extracts the contents of the Sqon to a directory.
     *
     * If the given directory path does not exist, it will be automatically
     * created. If one or more `$paths` are not specified, all paths in the
     * Sqon will be extracted to the directory. If `$overwrite` is true, any
     * path that already exists will be overwritten.
     *
     * @param string   $dir       The path to the directory to extract to.
     * @param string[] $paths     The paths in the Sqon to extract.
     * @param boolean  $overwrite Overwrite existing paths?
     */
    public function extractTo($dir, array $paths = [], $overwrite = true);

    /**
     * Returns the PHP bootstrap script for the Sqon.
     *
     * ```php
     * $bootstrap = $sqon->getBootstrap();
     * ```
     *
     * @return string The PHP bootstrap script.
     */
    public function getBootstrap();

    /**
     * Returns the event dispatcher.
     *
     * ```php
     * $dispatcher = $sqon->getEventDispatcher();
     *
     * if (null !== $dispatcher) {
     *     // ...
     * }
     * ```
     *
     * @return EventDispatcherInterface|null The event dispatcher.
     */
    public function getEventDispatcher();

    /**
     * Returns the path manager for a path stored in the Sqon.
     *
     * If the file contents were compressed, they are automatically
     * decompressed before being returned in a path manager.
     *
     * ```php
     * $path = $sqon->getPath('path/inside/sqon.php');
     * ```
     *
     * @param string $path The path in the Sqon.
     *
     * @return PathInterface The path manager.
     *
     * @throws SqonException If the path does not exist.
     */
    public function getPath($path);

    /**
     * Returns all of the paths stored in the Sqon as path managers.
     *
     * ```php
     * foreach ($sqon->getPaths() as $path) {
     *     // ...
     * }
     * ```
     *
     * @return Generator|PathInterface[] The path managers.
     */
    public function getPaths();

    /**
     * Checks if a path exists in the Sqon.
     *
     * ```php
     * if ($sqon->hasPath('path/inside/sqon.php')) {
     *     // ...
     * }
     * ```
     *
     * @param string $path The path to check for.
     *
     * @return boolean Returns `true` if it exists, `false` if not.
     */
    public function hasPath($path);

    /**
     * Checks if the signature for a Sqon is valid.
     *
     * ```php
     * if (Sqon::isValid('/path/to/example.sqon')) {
     *     // ...
     * }
     * ```
     *
     * @param string $path The path to the Sqon.
     *
     * @return boolean Returns `true` if it is valid, `false` if not.
     */
    public static function isValid($path);

    /**
     * Opens an existing Sqon.
     *
     * The `open()` verifies the signature and then parses the Sqon file to
     * extract the PHP bootstrap script and embedded database. Unlike the
     * `create()` method, the PHP bootstrap script and path information in
     * the database are preserved.
     *
     * ```php
     * $sqon = Sqon::open('/path/to/example.sqon');
     * ```
     *
     * @param string $path The path to the Sqon.
     *
     * @return SqonInterface The new Sqon manager.
     *
     * @throws SqonException If the Sqon does not exist.
     * @throws SqonException If the signature is not valid.
     */
    public static function open($path);

    /**
     * Removes a path in of the Sqon.
     *
     * ```php
     * $sqon->removePath('path/inside/sqon.php');
     * ```
     *
     * @param string $path The path to remove.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     */
    public function removePath($path);

    /**
     * Sets the PHP bootstrap script.
     *
     * If the script does not begin with `<?php` (excluding the shebang line)
     * or does not end with `__HALT_COMPILER();` an exception is thrown stating
     * the issue.
     *
     * ```php
     * $sqon->setBootstrap($script);
     * ```
     *
     * @param string $script The PHP bootstrap script.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     *
     * @throws SqonException If the script is not valid.
     */
    public function setBootstrap($script);

    /**
     * Sets the compression mode for newly set paths.
     *
     * By default, file contents are not compressed when added to the Sqon.
     * If a compression mode is set, new file contents added to the Sqon are
     * automatically compressed using the compression scheme chose through the
     * compression mode.
     *
     * ```php
     * $sqon->setCompression(Sqon::GZIP);
     * ```
     *
     * @param integer $mode The compression mode.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     */
    public function setCompression($mode);

    /**
     * Sets the event dispatcher.
     *
     * An event dispatcher can be registered with the Sqon manager to allow
     * for certain processes to be modified by event listeners. Each process
     * can be observed before or after an action has occurred. The following
     * processes can be observed:
     *
     * - `commit()`
     * - `extractTo()`
     * - `setBootstrap()`
     * - `setPath()`
     * - `setPathsUsingIterator()`
     *
     * > It is important to note that using this functionality could cause a
     * > a significant impact to performance for certain processes. An example
     * > would be registering a listener that modifies file contents as they
     * > are set in the Sqon, which would slow down the rate at which paths can
     * > be set in the Sqon.
     *
     * ```php
     * $sqon->setEventDispatcher(new EventDispatcher());
     * ```
     *
     * @param EventDispatcherInterface|null $dispatcher The event dispatcher.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     */
    public function setEventDispatcher(
        EventDispatcherInterface $dispatcher = null
    );

    /**
     * Sets the information for a path in the Sqon.
     *
     * ```php
     * $sqon->setPath('path/inside/sqon.php', $path);
     * ```
     *
     * @param string        $path    The path to set.
     * @param PathInterface $manager The path manager.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     */
    public function setPath($path, PathInterface $manager);

    /**
     * Sets one or more paths using a path manager iterator.
     *
     * The iterator is expected to provide a canonicalized, relative path as
     * the key and the corresponding path manager as the value. If an iterator
     * does not return the values as expected, an exception is thrown.
     *
     * ```php
     * $sqon->setUsingIterator($iterator);
     * ```
     *
     * @param Iterator $iterator The path manager iterator.
     *
     * @return SqonInterface A fluent interface to the Sqon manager.
     *
     * @throws SqonException If the iterator is not valid.
     */
    public function setPathsUsingIterator(Iterator $iterator);
}
