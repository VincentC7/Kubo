<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recette>
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

    public function findOneBySlug(string $slug): ?Recette
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return Recette[]
     */
    public function findByTag(string $tagNom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.tags', 't')
            ->where('t.nom = :nom')
            ->setParameter('nom', $tagNom)
            ->orderBy('r.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Recette[]
     */
    public function findByDifficulte(string $difficulte): array
    {
        return $this->findBy(['difficulte' => $difficulte], ['nom' => 'ASC']);
    }

    /**
     * @return Recette[]
     */
    public function findByTempsMaximal(int $minutesMax): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tempsTotal <= :max')
            ->setParameter('max', $minutesMax)
            ->orderBy('r.tempsTotal', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
