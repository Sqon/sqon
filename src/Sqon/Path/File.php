<?php

namespace Sqon\Path;

use Sqon\Exception\Path\PathException;

/**
 * Manages the path information for a file on disk.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class File implements PathInterface
{
    /**
     * The path to the file.
     *
     * @var string
     */
    private $path;

    /**
     * Initializes the new file path manager.
     *
     * @param string $path The path to the file.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (is_file($this->path)) {
            $contents = file_get_contents($this->path);

            if (false === $contents) {
                // @codeCoverageIgnoreStart
                throw new PathException(
                    "The contents for \"{$this->path}\" could not be read."
                );
                // @codeCoverageIgnoreEnd
            }

            return $contents;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModified()
    {
        $time = filemtime($this->path);

        if (false === $time) {
            // @codeCoverageIgnoreStart
            throw new PathException(
                "The last modified time for \"{$this->path}\" could not be determined."
            );
            // @codeCoverageIgnoreEnd
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        $permissions = fileperms($this->path);

        if (false === $permissions) {
            // @codeCoverageIgnoreStart
            throw new PathException(
                "The permission for \"{$this->path}\" could not be determined."
            );
            // @codeCoverageIgnoreEnd
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return is_dir($this->path) ? self::DIRECTORY : self::FILE;
    }
}
