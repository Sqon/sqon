<?php

namespace Sqon\Container;

use Generator;
use PDO;
use PDOException;
use PDOStatement;
use Sqon\Exception\Container\DatabaseException;
use Sqon\Path\Memory;
use Sqon\Path\PathInterface;

/**
 * Manages the SQLite database connection.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Database
{
    /**
     * The database connection.
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Initializes the new database connection manager.
     *
     * @param PDO $pdo The database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    /**
     * Starts the transaction.
     */
    public function begin()
    {
        $this->query('BEGIN TRANSACTION');
    }

    /**
     * Commits the transaction.
     */
    public function commit()
    {
        $this->query('COMMIT');
    }

    /**
     * Creates the database schema for a new Sqon.
     */
    public function createSchema()
    {
        $this->query(
            <<<SQL
CREATE TABLE paths (
    path VARCHAR(4096) NOT NULL,
    type INTEGER NOT NULL,
    compression INTEGER NOT NULL,
    modified INTEGER NOT NULL,
    permissions INTEGER NOT NULL,
    contents BLOG,

    PRIMARY KEY (path)
);
SQL
        );
    }

    /**
     * Returns the information for a path.
     *
     * @param string $path The path to retrieve.
     *
     * @return Memory The path information.
     *
     * @throws DatabaseException If the path does not exist.
     */
    public function getPath($path)
    {
        foreach ($this->select('paths', ['path' => $path]) as $info) {
            return new Memory(
                $info['contents'],
                $info['type'],
                $info['compression'],
                $info['modified'],
                $info['permissions']
            );
        }

        throw new DatabaseException("The path \"$path\" does not exist.");
    }

    /**
     * Yields all of the paths in the database.
     *
     * @return Generator|Memory[] All available paths.
     */
    public function getPaths()
    {
        foreach ($this->select('paths', []) as $info) {
            yield new Memory(
                $info['contents'],
                $info['type'],
                $info['compression'],
                $info['modified'],
                $info['permissions']
            );
        }
    }

    /**
     * Checks if a path exists.
     *
     * @param string $path The path to check for.
     *
     * @return boolean Returns `true` if it exists, `false` if not.
     */
    public function hasPath($path)
    {
        return ($this->count('paths', ['path' => $path]) > 0);
    }

    /**
     * Removes a path from the database.
     *
     * @param string $path The path to remove.
     */
    public function removePath($path)
    {
        $this->delete('paths', ['path' => $path]);
    }

    /**
     * Rolls back the transaction.
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
    }

    /**
     * Sets the information for a path.
     *
     * @param string        $path    The name of the path.
     * @param PathInterface $manager The path manager.
     */
    public function setPath($path, PathInterface $manager)
    {
        $this->replace(
            'paths',
            [
                'path' => $path,
                'type' => $manager->getType(),
                'compression' => $manager->getCompression(),
                'modified' => $manager->getModified(),
                'permissions' => $manager->getPermissions(),
                'contents' => $manager->getContents()
            ]
        );
    }

    /**
     * Counts the number of records.
     *
     * @param string $table The name of the table.
     * @param array  $where The WHERE clause.
     *
     * @return integer The number of records.
     */
    private function count($table, array $where)
    {
        $count = iterator_to_array($this->select($table, $where, ['COUNT(*)']));

        return (int) $count[0]['COUNT(*)'];
    }

    /**
     * Deletes an existing record.
     *
     * @param string $table The name of the table.
     * @param array  $where The WHERE clause.
     */
    private function delete($table, array $where)
    {
        $this->query(
            sprintf(
                'DELETE FROM %s%s',
                $table,
                $this->where($where)
            ),
            $where
        );
    }

    /**
     * Executes a prepared statement and returns the result.
     *
     * @param string $statement  The query statement.
     * @param array  $parameters The query parameters.
     *
     * @return PDOStatement The executed prepared statement.
     *
     * @throws DatabaseException If the query was not successful.
     */
    private function query($statement, array $parameters = [])
    {
        try {
            $query = $this->pdo->prepare($statement);
            $query->execute($parameters);

            return $query;

        // @codeCoverageIgnoreStart
        } catch (PDOException $exception) {
            throw new DatabaseException(
                'The query was not successful.',
                $exception
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Replaces a conflicting record or inserts a new one.
     *
     * @param string $table  The name of the table.
     * @param array  $values The column values.
     */
    private function replace($table, array $values)
    {
        $columns = array_keys($values);

        $this->query(
            sprintf(
                'REPLACE INTO %s (%s) VALUES (:%s)',
                $table,
                join(', ', $columns),
                join(', :', $columns)
            ),
            $values
        );
    }

    /**
     * Iterates through one or more selected records.
     *
     * @param string $table   The name of the table.
     * @param array  $where   The WHERE clause.
     * @param array  $columns The desired columns.
     *
     * @return Generator|array[] The selected records.
     */
    private function select($table, array $where, array $columns = ['*'])
    {
        $records = $this->query(
            sprintf(
                'SELECT %s FROM %s%s',
                join(', ', $columns),
                $table,
                $this->where($where)
            ),
            $where
        );

        try {
            foreach ($records as $record) {
                yield $record;
            }
        } finally {
            $records->closeCursor();
        }
    }

    /**
     * Creates the WHERE clause for a SQL statement.
     *
     * @param array $values The WHERE clause values.
     *
     * @return string The WHERE clause.
     */
    private function where(array $values)
    {
        if (empty($values)) {
            return '';
        }

        $clause = [];

        foreach (array_keys($values) as $column) {
            $clause[] = "$column = :$column";
        }

        return ' WHERE ' . join(', ', $clause);
    }
}
