<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

/**
 * class qui charge de manager la base de donnees
 */
abstract class AbstractRepository
{
    protected Connection $connection;
    protected string $tableName;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Insertion une ligne dans la table concerné
     * 
     * @param array<string, string|int> $data
     * @return int|string
     */
    public function create(array $data): int|string
    {
        return $this->connection->insert($this->tableName, $data);
    }

    /**
     * Supprimer un ou plusieurs lignes dans la table concerné
     * 
     * @param array<string, string|int> $criteria e.g: ['id' => 1] ou ['field' => value]
     * @return int|string
     */
    public function delete(array $criteria): int|string
    {
        return $this->connection->delete($this->tableName, $criteria);
    }
}
