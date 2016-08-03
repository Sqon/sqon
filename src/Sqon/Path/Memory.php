<?php

namespace Sqon\Path;

/**
 * Manages an individual path stored in memory.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Memory implements PathInterface
{
    /**
     * The file contents.
     *
     * @var null|string
     */
    private $contents;

    /**
     * The last modified Unix timestamp.
     *
     * @var integer
     */
    private $modified;

    /**
     * The Unix file permissions.
     *
     * @var integer
     */
    private $permissions;

    /**
     * The type of the path.
     *
     * @var integer
     */
    private $type;

    /**
     * Initializes the new in memory path manager.
     *
     * @param null|string $contents    The contents of the file.
     * @param integer     $type        The type of the path.
     * @param integer     $modified    The last modified Unix timestamp.
     * @param integer     $permissions The Unix file permissions.
     */
    public function __construct(
        $contents = null,
        $type = self::FILE,
        $modified = null,
        $permissions = 0644
    ) {
        if (null === $modified) {
            $modified = time();
        }

        $this->contents = $contents;
        $this->modified = $modified;
        $this->permissions = $permissions;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }
}
