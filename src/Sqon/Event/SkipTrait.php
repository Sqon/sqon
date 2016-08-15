<?php

namespace Sqon\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Adds the ability to skip an action after an event.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
trait SkipTrait
{
    /**
     * The skip flag.
     *
     * @var boolean
     */
    private $skipped = false;

    /**
     * Checks if the following action should be skipped.
     *
     * @return boolean Returns `true` if it should be skipped, `false` if not.
     */
    public function isSkipped()
    {
        return $this->skipped;
    }

    /**
     * @see Event::stopPropagation
     */
    abstract public function stopPropagation();

    /**
     * Skips the action following the event and halts further event propagation.
     */
    public function skip()
    {
        $this->stopPropagation();

        $this->skipped = true;
    }
}
