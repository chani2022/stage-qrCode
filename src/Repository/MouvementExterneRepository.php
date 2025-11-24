<?php

namespace App\Repository;

use App\Models\MouvementExterne;
use App\Models\PersonneExterne;
use App\Models\Vehicule;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class MouvementExterneRepository extends AbstractRepository
{
    protected string $tableName = 'appm_mouvement_externe';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function find(int $id): ?MouvementExterne
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id_mouvement_externe = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $data ? $this->hydrate($data) : null;
    }

    public function findByPhotoCin(string $photoCin): ?MouvementExterne
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('photo_cin = :photoCin')
            ->setParameter('photoCin', $photoCin)
            ->executeQuery()
            ->fetchAssociative();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * @return MouvementExterne[]
     */
    public function findByFilters(array $filters = []): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->orderBy('date_heure', 'DESC');

        if (!empty($filters['type'])) {
            $qb->andWhere('type = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('date_heure >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from'], Types::DATETIME_MUTABLE);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('date_heure <= :dateTo')
               ->setParameter('dateTo', $filters['date_to'], Types::DATETIME_MUTABLE);
        }

        if (!empty($filters['photo_cin'])) {
            $qb->andWhere('photo_cin = :photoCin')
               ->setParameter('photoCin', $filters['photo_cin']);
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $data);
    }

    public function getTodayMovements(): array
    {
        $today = new \DateTime();
        $startOfDay = clone $today;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $today;
        $endOfDay->setTime(23, 59, 59);

        return $this->findByFilters([
            'date_from' => $startOfDay,
            'date_to' => $endOfDay
        ]);
    }

    public function getLastMovementForPhotoCin(string $photoCin): ?MouvementExterne
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('photo_cin = :photoCin')
            ->orderBy('date_heure', 'DESC')
            ->setMaxResults(1)
            ->setParameter('photoCin', $photoCin)
            ->executeQuery()
            ->fetchAssociative();

        return $data ? $this->hydrate($data) : null;
    }

    public function save(MouvementExterne $mouvement): void
    {
        $data = [
            'type' => $mouvement->getType(),
            'date_heure' => $mouvement->getDateHeure()->format('Y-m-d H:i:s'),
            'photo_cin' => $mouvement->getPhotoCin(),
            'motif' => $mouvement->getMotif(),
            'observations' => $mouvement->getObservations(),
            'date_enregistrement' => $mouvement->getDateEnregistrement()->format('Y-m-d H:i:s'),
        ];

        if ($mouvement->getIdMouvementExterne() === null) {
            $this->connection->insert($this->tableName, $data);
            $mouvement->setIdMouvementExterne((int)$this->connection->lastInsertId());
        } else {
            $this->connection->update(
                $this->tableName,
                $data,
                ['id_mouvement_externe' => $mouvement->getIdMouvementExterne()]
            );
        }
    }

    public function delete(array $criteria): int|string
    {
        return parent::delete($criteria);
    }

    private function hydrate(array $data): MouvementExterne
    {
        return (new MouvementExterne(
            $data['id_mouvement_externe'],
            $data['type'],
            new \DateTime($data['date_heure']),
            $data['photo_cin'] ?? null,
            $data['motif'] ?? null,
            $data['observations'] ?? null,
            new \DateTime($data['date_enregistrement'])
        ));
    }
}
