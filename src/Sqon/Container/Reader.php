<?php

namespace Sqon\Container;

use KHerGe\File\FileInterface;
use Sqon\Exception\Container\ReaderException;

/**
 * Reads the contents of an existing Sqon.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Reader
{
    /**
     * The PHP bootstrap script size.
     *
     * @var integer
     */
    private $bootstrapSize;

    /**
     * The file manager.
     *
     * @var FileInterface
     */
    private $file;

    /**
     * The size of the Sqon.
     *
     * @var integer
     */
    private $size;

    /**
     * Initializes the new Sqon reader.
     *
     * @param FileInterface $file The file manager.
     */
    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    /**
     * Returns the PHP bootstrap script.
     *
     * @return string The PHP bootstrap script.
     *
     * @throws ReaderException If the script could not be returned.
     */
    public function getBootstrap()
    {
        $this->file->seek(0);

        return $this->file->read($this->getBootstrapSize());
    }

    /**
     * Extracts the database to a file manager.
     *
     * @param FileInterface $file The database file manager.
     *
     * @throws ReaderException If the database could not be extracted.
     */
    public function getDatabase(FileInterface $file)
    {
        $this->file->seek($this->getBootstrapSize());

        $file->stream(
            $this->file,
            $this->getSize() - $this->getBootstrapSize() - 20
        );
    }

    /**
     * Returns the raw SHA-1 hash signature.
     *
     * @return string The raw SHA-1 hash signature.
     */
    public function getSignature()
    {
        $this->file->seek(-20, FileInterface::RELATIVE_END);

        return $this->file->read(20);
    }

    /**
     * Returns the size of the PHP bootstrap script.
     *
     * @return integer The size of the PHP bootstrap script.
     *
     * @throws ReaderException If the size could not be determined.
     */
    private function getBootstrapSize()
    {
        if (null === $this->bootstrapSize) {
            $position = $this->file->tell();
            $sequence = '__HALT_COMPILER();';
            $length = strlen($sequence);
            $offset = 0;

            $this->file->seek(0);

            /*
             * The purpose of iterating through the file is so that a pattern,
             * "__HALT_COMPILER();" can be found. Once this pattern is found,
             * the current position is used as the size of the PHP bootstrap
             * script. The problem is that this is very slow and can probably
             * be optimized in the future.
             */
            foreach ($this->file->iterate(0, 1) as $char) {
                if ($sequence[$offset] === $char) {
                    $offset++;

                    if ($offset === $length) {
                        $this->bootstrapSize = $this->file->tell();

                        break;
                    }
                } else {
                    $offset = 0;
                }
            }

            $this->file->seek($position);

            if ($this->file->eof()) {
                // @codeCoverageIgnoreStart
                throw new ReaderException(
                    '`__HALT_COMPILER();` is missing in the PHP bootstrap script.'
                );
                // @codeCoverageIgnoreEnd
            }
        }

        return $this->bootstrapSize;
    }

    /**
     * Returns the size of the Sqon.
     *
     * @return integer The size of the Sqon.
     */
    private function getSize()
    {
        if (null === $this->size) {
            $position = $this->file->tell();

            $this->size = $this->file->size();

            $this->file->seek($position);
        }

        return $this->size;
    }
}
