<?php

set_error_handler(
    function ($code, $message, $file, $line) {
        throw new ErrorException($message, 0, $code, $file, $line);
    }
);

eval(
    call_user_func(
        function () {

            // The compiler halt offset.
            $offset = __COMPILER_HALT_OFFSET__;

            if (defined('HHVM_VERSION')) {
                $offset += 2;
            }

            // The size of the Sqon.
            $size = filesize(__FILE__);

            // The signature of the Sqon
            $signature = file_get_contents(
                __FILE__,
                false,
                null,
                $size - 20,
                20
            );

            // The path to the cache directory.
            $cache = join(
                DIRECTORY_SEPARATOR,
                [
                    getenv('SQON_TEMP') ?: sys_get_temp_dir(),
                    bin2hex($signature)
                ]
            );

            // The path to the database file.
            $database = $cache . DIRECTORY_SEPARATOR . 'sqon.db';

            // The path to the primary script.
            $primary = join(
                DIRECTORY_SEPARATOR,
                [$cache, 'files', '.sqon', 'primary.php']
            );

            /**
             * Extracts the embedded database to the cache.
             */
            $extract_database = function () use (&$database, &$offset, &$size) {
                $in = fopen(__FILE__, 'rb');
                $out = fopen($database, 'wb');

                stream_copy_to_stream(
                    $in,
                    $out,
                    $size - $offset - 20,
                    $offset
                );

                fclose($out);
                fclose($in);
            };

            /**
             * Creates the directory on disk.
             *
             * @param string $cache The path to the file cache.
             * @param string $info  The directory information.
             */
            $write_dir = function ($cache, $info) {
                $path = $cache . DIRECTORY_SEPARATOR . $info['path'];

                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }

                chmod($path, $info['permissions']);
                touch($path, $info['modified']);
            };

            /**
             * Creates the file on disk.
             *
             * @param string $cache The path to the file cache.
             * @param string $info  The directory information.
             */
            $write_file = function ($cache, $info) {
                $path = $cache . DIRECTORY_SEPARATOR . $info['path'];
                $base = dirname($path);

                if (!is_dir($base)) {
                    mkdir($base, 0755, true);
                }

                switch ($info['compression']) {
                    case 0:
                        break;

                    case 1:
                        $info['contents'] = gzdecode($info['contents']);
                        break;

                    case 2:
                        $info['contents'] = bzdecompress($info['contents']);
                        break;

                    default:
                        throw new UnexpectedValueException(
                            sprintf(
                                'The compression "%d" for "%s" in "%s" is not recognized.',
                                $path['compression'],
                                $path['path'],
                                __FILE__
                            )
                        );
                }

                file_put_contents($path, $info['contents']);

                chmod($path, $info['permissions']);
                touch($path, $info['modified']);
            };

            /**
             * Extracts the files in the database to the cache.
             */
            $extract_files = function () use (
                &$cache,
                &$database,
                &$write_dir,
                &$write_file
            ) {
                $files = $cache . DIRECTORY_SEPARATOR . 'files';

                mkdir($files, 0755, true);

                $pdo = new PDO(
                    "sqlite:$database",
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
                        case 0:
                            $write_file($files, $path);
                            break;

                        case 1:
                            $write_dir($files, $path);
                            break;

                        default:
                            throw new UnexpectedValueException(
                                sprintf(
                                    'The type "%d" for "%s" in "%s" is not recognized.',
                                    $path['type'],
                                    $path['path'],
                                    __FILE__
                                )
                            );
                    }
                }
            };

            /**
             * Checks if the signature is valid.
             *
             * @return boolean Returns `true` if it is, `false` if not.
             */
            $is_verified = function () use (&$signature, &$size) {
                $stream = fopen(__FILE__, 'rb');
                $context = hash_init('sha1');

                hash_update_stream(
                    $context,
                    $stream,
                    $size - 20
                );

                fclose($stream);

                return $signature === hash_final($context, true);
            };

            if (!is_dir($cache)) {
                if (!$is_verified()) {
                    throw new RuntimeException(
                        sprintf(
                            'The signature for "%s" is not valid.',
                            __FILE__
                        )
                    );
                }

                mkdir($cache, 0755, true);

                $extract_database();
                $extract_files();
            }

            restore_error_handler();

            if (is_file($primary)) {
                return "require '$primary';";
            }

            return '';
        }
    )
);

__HALT_COMPILER();
