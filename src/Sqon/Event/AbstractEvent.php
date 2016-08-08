<?php

namespace Sqon\Event;

use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Serves as the base class for the Sqon manager events.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class AbstractEvent extends Event
{
    /**
     * The Sqon manager.
     *
     * @var SqonInterface
     */
    private $sqon;

    /**
     * Initializes the new event.
     *
     * @param SqonInterface $sqon The Sqon manager.
     */
    public function __construct(SqonInterface $sqon)
    {
        $this->sqon = $sqon;
    }

    /**
     * Returns the Sqon manager.
     *
     * @return SqonInterface The Sqon manager.
     */
    public function getSqon()
    {
        return $this->sqon;
    }
}
