<?php

namespace Test;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Path\Memory;
use Sqon\Sqon;

/**
 * Verifies that the PHP bootstrap script functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class bootstrapTest extends TestCase
{
    /**
     * The path to the Sqon.
     *
     * @var string
     */
    private $path;

    /**
     * Verify that the PHP bootstrap script:
     *
     * - verifies the signature
     * - self extracts
     * - executes the primary script
     */
    public function testPhpBootstrapScriptFunctionsAsIntended()
    {
        self::assertEquals(
            'Hello, world!',
            exec(
                sprintf(
                    'php %s',
                    escapeshellarg($this->path)
                )
            ),
            'The PHP bootstrap script did not function as intended.'
        );
    }

    /**
     * Creates a new Sqon.
     */
    protected function setUp()
    {
        $this->path = tempnam(sys_get_temp_dir(), 'sqon-');

        Sqon::create($this->path)
            ->setPath(
                'hello.php',
                new Memory('<?php echo "Hello, world!\n";')
            )
            ->setPath(
                Sqon::PRIMARY,
                new Memory("<?php require __DIR__ . '/../hello.php';")
            )
            ->commit()
        ;
    }

    /**
     * Deletes the Sqon.
     */
    protected function tearDown()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
