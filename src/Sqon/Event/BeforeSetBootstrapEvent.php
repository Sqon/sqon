<?php

namespace Sqon\Event;

use Sqon\SqonInterface;

/**
 * Manages the event before a PHP bootstrap script is set by the Sqon manager.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BeforeSetBootstrapEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.before_set_bootstrap';

    /**
     * The PHP bootstrap script.
     *
     * @var string
     */
    private $script;

    /**
     * Initializes the new event.
     *
     * @param SqonInterface $sqon   The Sqon manager.
     * @param string        $script The PHP bootstrap script.
     */
    public function __construct(SqonInterface $sqon, $script)
    {
        parent::__construct($sqon);

        $this->script = $script;
    }

    /**
     * Returns the PHP bootstrap script.
     *
     * @return string The script.
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Sets the PHP bootstrap script.
     *
     * @param string $script The script.
     */
    public function setScript($script)
    {
        $this->script = $script;
    }
}
