<?php

namespace Sqon\Event;

use Iterator;
use Sqon\SqonInterface;

/**
 * Manages the event before the Sqon manager sets paths using an iterator.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BeforeSetPathsUsingIteratorEvent extends AbstractEvent
{
    /**
     * The name of the event.
     *
     * @var string
     */
    const NAME = 'sqon.before_set_paths_using_iterator';

    /**
     * The path iterator.
     *
     * @var Iterator
     */
    private $iterator;

    /**
     * Initializes the new event.
     *
     * @param SqonInterface $sqon     The Sqon manager.
     * @param Iterator      $iterator The path iterator.
     */
    public function __construct(SqonInterface $sqon, Iterator $iterator)
    {
        parent::__construct($sqon);

        $this->iterator = $iterator;
    }

    /**
     * Returns the path iterator.
     *
     * @return Iterator The path iterator.
     */
    public function getIterator()
    {
        return $this->iterator;
    }

    /**
     * Sets the path iterator.
     *
     * @param Iterator $iterator The path iterator.
     */
    public function setIterator(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }
}
