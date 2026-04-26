<?php

namespace App\Repository;

use App\Entity\PlanningEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningEntry>
 */
class PlanningEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEntry::class);
    }

    /** @return PlanningEntry[] */
    public function findByUserAndWeek(User $user, string $week): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('r')
            ->join('p.recette', 'r')
            ->where('p.user = :user')
            ->andWhere('p.week = :week')
            ->setParameter('user', $user)
            ->setParameter('week', $week)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUser(string $id, User $user): ?PlanningEntry
    {
        return $this->createQueryBuilder('p')
            ->addSelect('r')
            ->join('p.recette', 'r')
            ->where('p.id = :id')
            ->andWhere('p.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByUserRecetteWeek(User $user, \App\Entity\Recette $recette, string $week): ?PlanningEntry
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.recette = :recette')
            ->andWhere('p.week = :week')
            ->setParameter('user', $user)
            ->setParameter('recette', $recette)
            ->setParameter('week', $week)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
