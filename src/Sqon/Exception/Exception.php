<?php

namespace Sqon\Exception;

/**
 * Serves as the base exception class for the library.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Exception extends \Exception
{
    /**
     * Initializes the new exception.
     *
     * @param string     $message  The exception message.
     * @param \Exception $previous The previous exception.
     */
    public function __construct($message = '', \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
