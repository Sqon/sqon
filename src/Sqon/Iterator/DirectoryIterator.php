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
     * @param string $path The path to the directory.
     */
    public function __construct($path)
    {
        $this->base = '/^' . preg_quote($path, '/') . '/';

        $this->inner = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::CURRENT_AS_PATHNAME
                    | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                    | RecursiveDirectoryIterator::SKIP_DOTS
                    | RecursiveDirectoryIterator::UNIX_PATHS
            ),
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
