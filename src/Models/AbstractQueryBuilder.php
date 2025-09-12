<?php

namespace App\Models;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class AbstractQueryBuilder
{
    public function __construct(protected Connection $connection) {}

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
