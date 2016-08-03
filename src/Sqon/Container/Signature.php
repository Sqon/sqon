<?php

namespace Sqon\Container;

use KHerGe\File\FileInterface;

/**
 * Reads and generates raw SHA-1 signatures for file managers.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Signature
{
    /**
     * Generates a new raw SHA-1 hash signature for a Sqon file manager.
     *
     * @param FileInterface $file The Sqon file manager.
     *
     * @return string The new raw SHA-1 hash.
     */
    public function generate(FileInterface $file)
    {
        $context = hash_init('sha1');

        $file->seek(0);

        foreach ($file->iterate() as $buffer) {
            hash_update($context, $buffer);
        }

        return hash_final($context, true);
    }
}
