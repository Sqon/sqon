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
        exec(
            sprintf(
                'php %s',
                escapeshellarg($this->path)
            ),
            $output
        );

        self::assertEquals(
            "Hello, world!\nHello, bzip2!\nHello, gzip!",
            join("\n", $output),
            'The PHP bootstrap script did not function as intended.'
        );
    }

    /**
     * Creates a new Sqon.
     */
    protected function setUp()
    {
        $this->path = tempnam(sys_get_temp_dir(), 'sqon-');

        $hello = new Memory('<?php echo "Hello, world!\n";');
        $hello_bzip2 = new Memory('<?php echo "Hello, bzip2!\n";');
        $hello_gzip = new Memory('<?php echo "Hello, gzip!\n";');
        $primary = new Memory(
            <<<PHP
<?php

require __DIR__ . '/../hello.php';
require __DIR__ . '/../hello-bzip2.php';
require __DIR__ . '/../hello-gzip.php';
PHP
        );

        Sqon::create($this->path)
            ->setPath(Sqon::PRIMARY, $primary)
            ->setPath('hello.php', $hello)
            ->setCompression(Sqon::BZIP2)
            ->setPath('hello-bzip2.php', $hello_bzip2)
            ->setCompression(Sqon::GZIP)
            ->setPath('hello-gzip.php', $hello_gzip)
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
