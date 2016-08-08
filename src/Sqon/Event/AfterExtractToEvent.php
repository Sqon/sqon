<?php

namespace Sqon\Event;

use Sqon\SqonInterface;

/**
 * Manages the event after the Sqon managers extracts paths from a Sqon.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AfterExtractToEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.after_extract_to';

    /**
     * The path to the directory to extract to.
     *
     * @var string
     */
    private $dir;

    /**
     * The flag to overwrite paths.
     *
     * @var boolean
     */
    private $overwrite;

    /**
     * The paths in the Sqon to extract.
     *
     * @var string[]
     */
    private $paths;

    /**
     * Initializes the new event.
     *
     * @param SqonInterface $sqon      The Sqon manager.
     * @param string        $dir       The path to the directory to extract to.
     * @param string[]      $paths     The paths in the Sqon to extract.
     * @param boolean       $overwrite The flag to overwrite paths.
     */
    public function __construct(
        SqonInterface $sqon,
        $dir,
        array $paths,
        $overwrite
    ) {
        parent::__construct($sqon);

        $this->dir = $dir;
        $this->overwrite = $overwrite;
        $this->paths = $paths;
    }

    /**
     * Returns the path to the directory to extract to.
     *
     * @return string The directory path.
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Returns the paths in the Sqon to extract.
     *
     * @return string[] The paths.
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Returns the flag to overwrite paths.
     *
     * @return boolean The overwrite flag.
     */
    public function isOverwrite()
    {
        return $this->overwrite;
    }
}
