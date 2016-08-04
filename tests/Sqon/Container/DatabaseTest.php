<?php

namespace Test\Sqon\Container;

use PDO;
use PDOStatement;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Container\Database;
use Sqon\Exception\Container\DatabaseException;
use Sqon\Path\Memory;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the database manager functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Container\Database
 */
class DatabaseTest extends TestCase
{
    use TempTrait;

    /**
     * The prepared INSERT statement.
     *
     * @var PDOStatement
     */
    private $insert;

    /**
     * The database manager.
     *
     * @var Database
     */
    private $manager;

    /**
     * The database connection.
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Verify that a transaction can be managed.
     */
    public function testManageATransactionState()
    {
        $this->manager->begin();
        $this->manager->commit();
        $this->manager->begin();
        $this->manager->rollback();
    }

    /**
     * Verify that the path information can be retrieved.
     */
    public function testRetrieveExistingPathInformation()
    {
        $this->insert->execute($this->values);

        $path = $this->manager->getPath($this->values['path']);

        self::assertEquals(
            $this->values['contents'],
            $path->getContents(),
            'The file contents were not returned properly.'
        );

        self::assertEquals(
            $this->values['modified'],
            $path->getModified(),
            'The last modified Unix timestamp was not returned properly.'
        );

        self::assertEquals(
            $this->values['permissions'],
            $path->getPermissions(),
            'The Unix file permissions were not returned properly.'
        );

        self::assertEquals(
            $this->values['type'],
            $path->getType(),
            'The type of the path was not returned properly.'
        );
    }

    /**
     * Verify that an exception is thrown if a file does not exist.
     *
     * @expectedException \Sqon\Exception\Container\DatabaseException
     * @expectedExceptionMessage The path "test.php" does not exist.
     */
    public function testRetrievingAFileThatDoesNotExistThrowsAnException()
    {
        $this->manager->getPath($this->values['path']);
    }

    /**
     * Verify that all path scan be retrieved.
     */
    public function testRetrieveAllAvailablePaths()
    {
        $this->insert->execute($this->values);

        foreach ($this->manager->getPaths() as $path => $manager) {
            self::assertEquals(
                $this->values['path'],
                $path,
                'The path was not returned properly.'
            );

            self::assertEquals(
                $this->values['contents'],
                $manager->getContents(),
                'The file contents were not returned properly.'
            );

            self::assertEquals(
                $this->values['modified'],
                $manager->getModified(),
                'The last modified Unix timestamp was not returned properly.'
            );

            self::assertEquals(
                $this->values['permissions'],
                $manager->getPermissions(),
                'The Unix file permissions were not returned properly.'
            );

            self::assertEquals(
                $this->values['type'],
                $manager->getType(),
                'The type of the path was not returned properly.'
            );
        }
    }

    /**
     * Verify that we can check if a path exists.
     */
    public function testCheckIfAPathExists()
    {
        self::assertFalse(
            $this->manager->hasPath($this->values['path']),
            'The path should not exist.'
        );

        $this->insert->execute($this->values);

        self::assertTrue(
            $this->manager->hasPath($this->values['path']),
            'The path should now exist.'
        );
    }

    /**
     * Verify that path can be removed.
     */
    public function testRemoveAnExistingPath()
    {
        $this->insert->execute($this->values);

        $this->manager->removePath($this->values['path']);
    }

    /**
     * Verify that a path can be set.
     */
    public function testSetAPath()
    {
        $this->manager->setPath(
            $this->values['path'],
            new Memory(
                $this->values['contents'],
                $this->values['type'],
                $this->values['modified'],
                $this->values['permissions']
            )
        );

        $query = $this->pdo->query(
            "SELECT * FROM paths WHERE path = '{$this->values['path']}'"
        );

        $records = $query->fetchAll();

        $query->closeCursor();

        self::assertEquals(
            $this->values,
            $records[0],
            'The path was not inserted properly.'
        );
    }

    /**
     * Verify that contents can be compressed using bzip2.
     */
    public function testSetAPathUsingBzip2Compression()
    {
        $this->manager->setCompression(Database::BZIP2);
        $this->manager->setPath(
            $this->values['path'],
            new Memory(
                $this->values['contents'],
                $this->values['type'],
                $this->values['modified'],
                $this->values['permissions']
            )
        );

        $query = $this->pdo->query(
            "SELECT * FROM paths WHERE path = '{$this->values['path']}'"
        );

        $records = $query->fetchAll();

        $query->closeCursor();

        self::assertEquals(
            array_merge(
                $this->values,
                [
                    'compression' => Database::BZIP2,
                    'contents' => bzcompress($this->values['contents'])
                ]
            ),
            $records[0],
            'The contents were not compressed using bzip2.'
        );

        $path = $this->manager->getPath($this->values['path']);

        self::assertEquals(
            $this->values['contents'],
            $path->getContents(),
            'The contents were not decompressed after selection.'
        );
    }

    /**
     * Verify that contents can be compressed using gzip.
     */
    public function testSetAPathUsingGzipCompression()
    {
        $this->manager->setCompression(Database::GZIP);
        $this->manager->setPath(
            $this->values['path'],
            new Memory(
                $this->values['contents'],
                $this->values['type'],
                $this->values['modified'],
                $this->values['permissions']
            )
        );

        $query = $this->pdo->query(
            "SELECT * FROM paths WHERE path = '{$this->values['path']}'"
        );

        $records = $query->fetchAll();

        $query->closeCursor();

        self::assertEquals(
            array_merge(
                $this->values,
                [
                    'compression' => Database::GZIP,
                    'contents' => gzencode($this->values['contents'])
                ]
            ),
            $records[0],
            'The contents were not compressed using gzip.'
        );

        $path = $this->manager->getPath($this->values['path']);

        self::assertEquals(
            $this->values['contents'],
            $path->getContents(),
            'The contents were not decompressed after selection.'
        );
    }

    /**
     * Creates a new database manager.
     */
    protected function setUp()
    {
        $this->pdo = new PDO(
            'sqlite:' . $this->createTemporaryFile(),
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );

        $this->manager = new Database($this->pdo);
        $this->manager->createSchema();

        $columns = [
            'path',
            'type',
            'compression',
            'modified',
            'permissions',
            'contents'
        ];

        $this->insert = $this->pdo->prepare(
            sprintf(
                'INSERT INTO paths (%s) VALUES (:%s)',
                join(', ', $columns),
                join(', :', $columns)
            )
        );

        $this->values = [
            'compression' => Database::NONE,
            'path' => 'test.php',
            'type' => Memory::FILE,
            'modified' => time(),
            'permissions' => 0644,
            'contents' => 'test'
        ];
    }
}
