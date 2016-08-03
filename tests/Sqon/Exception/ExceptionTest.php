<?php

namespace Test\Sqon\Exception;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Exception\Exception;

/**
 * Verifies that the base exception class functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Exception\Exception
 */
class ExceptionTest extends TestCase
{
    /**
     * Verify that a new exception can be created without arguments.
     */
    public function testCreateNewExceptionWithoutArguments()
    {
        new Exception();
    }

    /**
     * Verify that a new exception can be created with arguments.
     */
    public function testCreateNewExceptionWithArguments()
    {
        $message = 'This is a test exception message.';
        $previous = new \Exception();
        $exception = new Exception($message, $previous);

        self::assertEquals(
            $message,
            $exception->getMessage(),
            'The exception message was not set properly.'
        );

        self::assertSame(
            $previous,
            $exception->getPrevious(),
            'The previous exception was not set.'
        );
    }
}
