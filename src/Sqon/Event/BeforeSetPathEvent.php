<?php

namespace Sqon\Event;

use Sqon\Path\PathInterface;
use Sqon\SqonInterface;

/**
 * Manages the event before a path is set by the Sqon manager.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BeforeSetPathEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.before_set_path';

    /**
     * The manager for the file or directory.
     *
     * @var PathInterface
     */
    private $manager;

    /**
     * The path to the file or directory.
     *
     * @var string
     */
    private $path;

    /**
     * Initializes the new event.
     *
     * @param SqonInterface $sqon    The Sqon manager.
     * @param string        $path    The path to the file or directory.
     * @param PathInterface $manager The manager for the file or directory.
     */
    public function __construct(
        SqonInterface $sqon,
        $path,
        PathInterface $manager
    ) {
        parent::__construct($sqon);

        $this->manager = $manager;
        $this->path = $path;
    }

    /**
     * Returns the manager for the file or directory.
     *
     * @return PathInterface The path manager.
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Returns the path to the file or directory.
     *
     * @return string The path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the manager for the file or directory.
     *
     * @param PathInterface $manager The path manager.
     */
    public function setManager(PathInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Sets the path to the file or directory.
     *
     * @param string $path The path.
     */
    public function setPath($path)
    {
        $this->path = $path;
    }
}
