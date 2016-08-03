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
     * @param FileInterface $file   The Sqon file manager.
     * @param boolean       $signed The Sqon has a signature?
     *
     * @return string The new raw SHA-1 hash.
     */
    public function generate(FileInterface $file, $signed = false)
    {
        $context = hash_init('sha1');
        $bytes = 0;

        if ($signed) {
            $bytes = $file->size() - 20;
        }

        $file->seek(0);

        foreach ($file->iterate($bytes) as $buffer) {
            hash_update($context, $buffer);
        }

        return hash_final($context, true);
    }
}
