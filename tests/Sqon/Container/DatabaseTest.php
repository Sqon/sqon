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
            $this->values['compression'],
            $path->getCompression(),
            'The compression mode was not returned properly.'
        );

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
     */
    public function testRetrievingAFileThatDoesNotExistThrowsAnException()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('The path "test.php" does not exist.');

        $this->manager->getPath($this->values['path']);
    }

    /**
     * Verify that all path scan be retrieved.
     */
    public function testRetrieveAllAvailablePaths()
    {
        $this->insert->execute($this->values);

        foreach ($this->manager->getPaths() as $path) {
            self::assertEquals(
                $this->values['compression'],
                $path->getCompression(),
                'The compression mode was not returned properly.'
            );

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
                $this->values['compression'],
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
            'path' => 'test.php',
            'type' => Memory::FILE,
            'compression' => Memory::GZIP,
            'modified' => time(),
            'permissions' => 0644,
            'contents' => 'test'
        ];
    }
}
