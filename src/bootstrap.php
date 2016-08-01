#!/usr/bin/env php
<?php

namespace
{
    eval((new Sqon\Bootstrap(__FILE__))->run());
}

namespace Sqon
{
    use ErrorException;
    use PDO;
    use RuntimeException;
    use UnexpectedValueException;

    /**
     * Manages the bootstrapping process for the Sqon.
     *
     * @author Kevin Herrera <kevin@herrera.io>
     */
    class Bootstrap
    {
        /**
         * Indicates that bzip2 was used for compression.
         *
         * @var integer
         */
        const BZIP2 = 2;

        /**
         * Indicates that the path is for a directory.
         *
         * @var integer
         */
        const DIRECTORY = 1;

        /**
         * Indicates that the path is for a file.
         *
         * @var integer
         */
        const FILE = 0;

        /**
         * Indicates that gzip was used for compression.
         *
         * @var integer
         */
        const GZIP = 1;

        /**
         * Indicates that no compression is used.
         *
         * @var integer
         */
        const NONE = 0;

        /**
         * The cache directory path.
         *
         * @var string
         */
        private $cacheDir;

        /**
         * The database file path.
         *
         * @var string
         */
        private $databaseFile;

        /**
         * The path to the Sqon.
         *
         * @var string
         */
        private $path;

        /**
         * The path to the primary script.
         *
         * @var string
         */
        private $primaryFile;

        /**
         * The signature for the Sqon.
         *
         * @var string
         */
        private $signature;

        /**
         * The size of the Sqon.
         *
         * @var integer
         */
        private $size;

        /**
         * Initializes the new bootstrapper.
         *
         * @param string $path The path to the Sqon.
         */
        public function __construct($path)
        {
            $this->registerErrorHandler();

            $this->path = $path;
            $this->size = filesize($path);
        }

        /**
         * Performs the bootstrapping process.
         */
        public function run()
        {
            if (!$this->isVerified()) {
                throw new RuntimeException(
                    sprintf(
                        'The Sqon "%s" is corrupt.',
                        $this->path
                    )
                );
            }

            if (!$this->isCacheAvailable()) {
                $this->createCacheDir();
                $this->extractDatabase();
                $this->extractFiles();
            }

            restore_error_handler();

            if ($this->isPrimaryAvailable()) {
                return sprintf('require \'%s\';', $this->getPrimaryFile());
            }

            return '';
        }

        /**
         * Creates the cache directory path.
         */
        private function createCacheDir()
        {
            mkdir($this->getCacheDir(), 0755, true);
        }

        /**
         * Creates a cache directory.
         *
         * @param array $dir The directory information.
         */
        private function createDir(array $dir)
        {
            $path = join(
                DIRECTORY_SEPARATOR,
                [
                    $this->getCacheDir(),
                    'files',
                    $dir['path']
                ]
            );

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            chmod($path, $dir['permissions']);
            touch($path, $dir['modified']);
        }

        /**
         * Creates a cache file.
         *
         * @param array $file The file information.
         */
        private function createFile(array $file)
        {
            $path = join(
                DIRECTORY_SEPARATOR,
                [
                    $this->getCacheDir(),
                    'files',
                    $file['path']
                ]
            );

            $dir = dirname($path);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            switch ($file['compression']) {
                case self::NONE:
                    break;

                case self::GZIP:
                    $file['contents'] = gzdecode($file['contents']);
                    break;

                case self::BZIP2:
                    $file['contents'] = bzdecompress($file['contents']);
                    break;

                default:
                    throw new UnexpectedValueException(
                        sprintf(
                            'The compression mode "%d" for the file "%s" was not recognized.',
                            $file['compression'],
                            $file['path']
                        )
                    );
            }

            file_put_contents($path, $file['contents']);

            chmod($path, $file['permissions']);
            touch($path, $file['modified']);
        }

        /**
         * Extracts the embedded database to the cache.
         */
        private function extractDatabase()
        {
            $in = fopen(__FILE__, 'rb');
            $out = fopen($this->getDatabaseFile(), 'wb');

            stream_copy_to_stream(
                $in,
                $out,
                $this->size - __COMPILER_HALT_OFFSET__ - 20,
                __COMPILER_HALT_OFFSET__
            );

            fclose($out);
            fclose($in);
        }

        /**
         * Extracts the files in the database to the cache.
         */
        private function extractFiles()
        {
            $pdo = new PDO(
                'sqlite:' . $this->getDatabaseFile(),
                null,
                null,
                [
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );

            $paths = $pdo->query('SELECT * FROM paths');

            foreach ($paths as $path) {
                switch ($path['type']) {
                    case self::FILE:
                        $this->createFile($path);
                        break;

                    case self::DIRECTORY:
                        $this->createDir($path);
                        break;

                    default:
                        throw new UnexpectedValueException(
                            sprintf(
                                'The type (%d) of the path "%s" is not recognized.',
                                $path['type'],
                                $path['path']
                            )
                        );
                }
            }
        }

        /**
         * Returns the cache directory path.
         *
         * @return string The path.
         */
        private function getCacheDir()
        {
            if (null === $this->cacheDir) {
                $this->cacheDir = join(
                    DIRECTORY_SEPARATOR,
                    [
                        $this->getTempDir(),
                        bin2hex($this->getSignature())
                    ]
                );
            }

            return $this->cacheDir;
        }

        /**
         * Returns the database file path.
         *
         * @return string The path.
         */
        private function getDatabaseFile()
        {
            if (null === $this->databaseFile) {
                $this->databaseFile = join(
                    DIRECTORY_SEPARATOR,
                    [
                        $this->getCacheDir(),
                        'sqon.db'
                    ]
                );
            }

            return $this->databaseFile;
        }

        /**
         * Returns the primary script file path.
         *
         * @return string The path.
         */
        private function getPrimaryFile()
        {
            if (null === $this->primaryFile) {
                $this->primaryFile = join(
                    DIRECTORY_SEPARATOR,
                    [
                        $this->getCacheDir(),
                        'files',
                        '.sqon',
                        'primary.php'
                    ]
                );
            }

            return $this->primaryFile;
        }

        /**
         * Returns the signature for the Sqon.
         *
         * @return string The signature.
         */
        private function getSignature()
        {
            if (null === $this->signature) {
                $this->signature = file_get_contents(
                    $this->path,
                    false,
                    null,
                    $this->size - 20,
                    20
                );
            }

            return $this->signature;
        }

        /**
         * Returns the temporary directory path.
         *
         * @return string The path.
         */
        private function getTempDir()
        {
            return getenv('SQON_TEMP') ?: sys_get_temp_dir();
        }

        /**
         * Checks if the Sqon cache is available.
         *
         * @return boolean Returns `true` if available, `false` if not.
         */
        private function isCacheAvailable()
        {
            return is_dir($this->getCacheDir());
        }

        /**
         * Checks if the primary script is available.
         *
         * @return boolean Returns `true` if available, `false` if not.
         */
        private function isPrimaryAvailable()
        {
            return is_file($this->getPrimaryFile());
        }

        /**
         * Checks if the signature for the Sqon is valid.
         *
         * @return boolean Returns `true` if verified, `false` if not.
         */
        private function isVerified()
        {
            return $this->getSignature() === $this->makeSignature();
        }

        /**
         * Creates a new signature for the Sqon.
         *
         * @return string The new signature.
         */
        private function makeSignature()
        {
            $stream = fopen($this->path, 'rb');
            $context = hash_init('sha1');

            hash_update_stream(
                $context,
                $stream,
                $this->size - 20
            );

            fclose($stream);

            return hash_final($context, true);
        }

        /**
         * Registers a custom error handler.
         */
        private function registerErrorHandler()
        {
            set_error_handler(
                function ($code, $message, $file, $line) {
                    throw new ErrorException($message, 0, $code, $file, $line);
                }
            );
        }
    }
}

__HALT_COMPILER();
