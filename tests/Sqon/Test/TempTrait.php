<?php

namespace Test\Sqon\Test;

use KHerGe\File\File;
use KHerGe\File\FileInterface;

/**
 * Manages the creation and clean up of temporary files.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
trait TempTrait
{
    /**
     * The temporary files.
     *
     * @var string[]
     */
    private $temp = [];

    /**
     * Automatically deletes temporary files.
     */
    protected function tearDown()
    {
        $this->deleteTemporaryFiles();
    }

    /**
     * Creates a new temporary file.
     *
     * @return string The new temporary file.
     */
    private function createTemporaryFile()
    {
        return $this->temp[] = tempnam(sys_get_temp_dir(), 'sqon-');
    }

    /**
     * Creates a file manager for a new temporary file.
     *
     * @param string $mode The file open mode.
     *
     * @return FileInterface The file manager.
     */
    private function createTemporaryFileManager($mode = 'w+')
    {
        return new File($this->createTemporaryFile(), $mode);
    }

    /**
     * Deletes the temporary files.
     */
    private function deleteTemporaryFiles()
    {
        foreach ($this->temp as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->temp = [];
    }
}
