<?php

namespace Sqon\Path;

use Sqon\Exception\Path\PathException;

/**
 * Defines the public interface for a path manager.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
interface PathInterface
{
    /**
     * Indicates that the contents are compressed using bzip2.
     *
     * @var integer
     */
    const BZIP2 = 2;

    /**
     * Indicates that the path is a directory.
     *
     * @var integer
     */
    const DIRECTORY = 1;

    /**
     * Indicates that the path is a file.
     *
     * @var integer
     */
    const FILE = 0;

    /**
     * Indicates that the contents are compressed using gzip.
     *
     * @var integer
     */
    const GZIP = 1;

    /**
     * Indicates that the contents are not compressed.
     *
     * @var integer
     */
    const NONE = 0;

    /**
     * Returns the compression mode for the file contents.
     *
     * @return integer The compression mode.
     */
    public function getCompression();

    /**
     * Returns the contents of the file.
     *
     * @return null|string The contents of the file.
     *
     * @throws PathException If the contents could not be returned.
     */
    public function getContents();

    /**
     * Returns the last modified Unix timestamp.
     *
     * @return integer The Unix timestamp.
     *
     * @throws PathException If the timestamp could not be returned.
     */
    public function getModified();

    /**
     * Returns the Unix file permissions as decimal.
     *
     * @return integer The Unix file permissions.
     *
     * @throws PathException If the permissions could not be returned.
     */
    public function getPermissions();

    /**
     * Returns the type of the path.
     *
     * @return integer The type of the path.
     *
     * @throws PathException If the type could not be returned.
     */
    public function getType();
}
