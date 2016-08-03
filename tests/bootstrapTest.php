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
     * The Sqon manager.
     *
     * @var Sqon
     */
    private $sqon;

    /**
     * Verify that the PHP bootstrap script:
     *
     * - verifies the signature
     * - self extracts
     * - executes the primary script
     */
    public function testPhpBootstrapScriptFunctionsAsIntended()
    {
        $hello = new Memory('<?php echo "Hello, world!\n";');
        $primary = new Memory("<?php require __DIR__ . '/../hello.php';");
        $bootstrap = str_replace(
            "<?php\n",
            "<?php\n\necho __COMPILER_HALT_OFFSET__, \"\\n\";\n",
            Sqon::createBootstrap()
        );

        $this
            ->sqon
            ->setBootstrap($bootstrap)
            ->setPath(Sqon::PRIMARY, $primary)
            ->setPath('hello.php', $hello)
            ->commit()
        ;

        exec(
            sprintf(
                'php %s',
                escapeshellarg($this->path)
            ),
            $output
        );

        self::assertEquals(
            "6536\nHello, world!",
            join("\n", $output),
            'The PHP bootstrap script did not function as intended.'
        );
    }

    /**
     * Verify that the PHP bootstrap script:
     *
     * - verifies the signature
     * - self extracts
     * - executes the primary script
     *
     * with varying compression modes.
     */
    public function testPhpBootstrapScriptFunctionsAsIntendedWithCompression()
    {
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

        $this
            ->sqon
            ->setPath(Sqon::PRIMARY, $primary)
            ->setPath('hello.php', $hello)
            ->setCompression(Sqon::BZIP2)
            ->setPath('hello-bzip2.php', $hello_bzip2)
            ->setCompression(Sqon::GZIP)
            ->setPath('hello-gzip.php', $hello_gzip)
            ->commit()
        ;

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
        $this->sqon = Sqon::create($this->path);
    }

    /**
     * Deletes the Sqon.
     */
    protected function tearDown()
    {
        $this->sqon = null;

        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
