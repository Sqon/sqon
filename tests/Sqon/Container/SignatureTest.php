<?php

namespace Test\Sqon\Container;

use KHerGe\File\File;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Container\Signature;

/**
 * Verifies that the signature generator functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Container\Signature
 */
class SignatureTest extends TestCase
{
    /**
     * Verify that a new raw signature can be generated for a file manager.
     */
    public function testGenerateNewSignatureForAFileManager()
    {
        $file = new File('php://memory', 'w+');
        $file->write('test');

        $hash = hash('sha1', 'test', true);
        $signature = new Signature();

        self::assertEquals(
            $hash,
            $signature->generate($file),
            'The new signature was not generated properly.'
        );

        $file->write($hash);

        self::assertEquals(
            $hash,
            $signature->generate($file, true),
            'The new signature was not generated properly.'
        );
    }
}
