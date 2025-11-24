<?php

namespace App\Repository;

use App\Models\Vehicule;
use Doctrine\DBAL\Connection;

class VehiculeRepository extends AbstractRepository
{
    protected string $tableName = 'appm_vehicule';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function find(int $id): ?Vehicule
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_vehicule = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findBy(array $filters): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName);

        if (!empty($filters['personnel_id'])) {
            $qb->andWhere('id_personnel = :personnelId')
               ->setParameter('personnelId', $filters['personnel_id']);
        }


        if (!empty($filters['parking'])) {
            $qb->andWhere('parking = :parking')
               ->setParameter('parking', $filters['parking']);
        }

        if (!empty($filters['date'])) {
            $qb->andWhere('DATE(date_enregistrement) = :date')
               ->setParameter('date', $filters['date']);
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $data);
    }

    public function delete(array $criteria): int|string
    {
        return $this->connection->delete($this->tableName, $criteria);
    }

    public function findByPersonnel(int $personnelId): array
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_personnel = :personnelId')
            ->setParameter('personnelId', $personnelId)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $data);
    }

    public function findOneByPersonnel(int $personnelId): ?Vehicule
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_personnel = :personnelId')
            ->setParameter('personnelId', $personnelId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $data ? $this->hydrate($data) : null;
    }

    public function save(Vehicule $vehicule): void
    {
        $data = [
            'photo' => $vehicule->getPhoto(),
            'date_enregistrement' => $vehicule->getDateEnregistrement()->format('Y-m-d H:i:s'),
            'parking' => $vehicule->getParking(),
            'id_personnel' => $vehicule->getIdPersonnel(),
        ];

        if ($vehicule->getIdVehicule() === null) {
            $this->connection->insert($this->tableName, $data);
            $vehicule->setIdVehicule((int)$this->connection->lastInsertId());
        } else {
            $this->connection->update(
                $this->tableName,
                $data,
                ['id_vehicule' => $vehicule->getIdVehicule()]
            );
        }
    }

    private function hydrate(array $data): Vehicule
    {
        return new Vehicule(
            $data['id_vehicule'],
            $data['photo'] ?? null,
            new \DateTime($data['date_enregistrement']),
            $data['parking'] ?? 'non',
            $data['id_personnel'] ? (int)$data['id_personnel'] : null
        );
    }
}
