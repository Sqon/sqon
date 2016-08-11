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
     * The `$base` directory path is used to convert the `$path` into a path
     * that is relative to the `$base` path. If a `$base` path is not given,
     * the `$path` itself will be used as the `$base` path.
     *
     * @param string $path The path to the directory.
     * @param string $base The base directory path.
     */
    public function __construct($path, $base = null)
    {
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
        return preg_replace($this->base, '', $this->inner->key());
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
