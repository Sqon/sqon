<?php

namespace Sqon\Container;

use KHerGe\File\FileInterface;

/**
 * Write a new Sqon using its base components.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Writer
{
    /**
     * Writes the contents of the Sqon.
     *
     * @param FileInterface $sqon      The Sqon file manager.
     * @param FileInterface $bootstrap The PHP bootstrap script file manager.
     * @param FileInterface $database  The database file manager.
     */
    public function write(
        FileInterface $sqon,
        FileInterface $bootstrap,
        FileInterface $database
    ) {
        $sqon->stream($bootstrap);
        $sqon->stream($database);
        $sqon->seek(0);
        $sqon->write((new Signature())->generate($sqon));
    }
}
