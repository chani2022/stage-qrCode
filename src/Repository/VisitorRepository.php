<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class VisitorRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->tableName = 'appm_personne_externe';
    }
}
