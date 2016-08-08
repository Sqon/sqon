<?php

namespace Sqon\Event;

/**
 * Manages the event before a Sqon manager commits the changes.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BeforeCommitEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.before_commit';
}
