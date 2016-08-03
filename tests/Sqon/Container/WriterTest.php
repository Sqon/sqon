<?php

namespace Test\Sqon\Container;

use KHerGe\File\File;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Container\Writer;

/**
 * Verifies that the Sqon writer functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class WriterTest extends TestCase
{
    /**
     * Verify that a new Sqon is written.
     */
    public function testWriteTheContentsOfANewSqon()
    {
        $bootstrap = new File('php://memory', 'w+');
        $bootstrap->write('bootstrap');
        $bootstrap->seek(0);

        $database = new File('php://memory', 'w+');
        $database->write('database');
        $database->seek(0);

        $sqon = new File('php://memory', 'w+');

        (new Writer())->write($sqon, $bootstrap, $database);

        $sqon->seek(0);

        self::assertEquals(
            'bootstrapdatabase' . hash('sha1', 'bootstrapdatabase', true),
            $sqon->read(),
            'The contents of the Sqon file were not written properly.'
        );
    }
}
