<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

abstract class AbstractRepository
{
    protected Connection $connection;
    protected string $tableName;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(array $data): int|string
    {
        return $this->connection->insert($this->tableName, $data);
    }

    public function delete(array $criteria): int|string
    {
        return $this->connection->delete($this->tableName, $criteria);
    }
}
