<?php

namespace Sqon\Iterator;

use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sqon\Path\File;

/**
 * Recursively iterators through a directory and returns path managers.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class DirectoryIterator implements Iterator
{
    /**
     * The alternative path.
     *
     * @var string
     */
    private $alternative;

    /**
     * The base directory path.
     *
     * @var string
     */
    private $base;

    /**
     * The inner iterator.
     *
     * @var RecursiveIteratorIterator
     */
    private $inner;

    /**
     * Initializes the new directory iterator.
     *
     * By default, the `$path` is used as the base directory path for making
     * paths returned by the iterator into relative paths. An alternative path
     * can be set as the `$base` path. If an `$alternative` path is provided,
     * it is used to replace the `$base` path (e.g. `/base/path/to` becomes
     * `alternative/path/to`).
     *
     * @param string      $path        The path to the directory.
     * @param null|string $base        The base directory path.
     * @param string      $alternative The path to replace the base directory path with.
     */
    public function __construct($path, $base = null, $alternative = '')
    {
        $this->alternative = $alternative;

        if (null === $base) {
            $base = $path;
        }

        $this->base = '/^' . preg_quote($base, '/') . '/';

        $directory = new RecursiveDirectoryIterator($path);
        $directory->setFlags(
            $directory->getFlags()
                | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                | RecursiveDirectoryIterator::SKIP_DOTS
                | RecursiveDirectoryIterator::UNIX_PATHS
        );

        $this->inner = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return new File($this->inner->current());
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return preg_replace(
            $this->base,
            $this->alternative,
            $this->inner->key()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->inner->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->inner->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->inner->valid();
    }
}
