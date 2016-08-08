<?php

namespace Sqon\Event;

/**
 * Manages the event after a Sqon manager commits the changes.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AfterCommitEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.after_commit';
}
