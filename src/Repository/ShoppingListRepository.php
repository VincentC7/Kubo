<?php

namespace App\Repository;

use App\Entity\ShoppingList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingList>
 */
class ShoppingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingList::class);
    }

    public function findOneByUserAndWeek(User $user, string $week): ?ShoppingList
    {
        return $this->createQueryBuilder('s')
            ->addSelect('i')
            ->leftJoin('s.items', 'i')
            ->where('s.user = :user')
            ->andWhere('s.week = :week')
            ->setParameter('user', $user)
            ->setParameter('week', $week)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
