<?php

namespace App\Repository;

use App\Entity\ShoppingItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingItem>
 */
class ShoppingItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingItem::class);
    }

    public function findOneByIdAndUser(string $id, User $user): ?ShoppingItem
    {
        return $this->createQueryBuilder('i')
            ->join('i.shoppingList', 'l')
            ->where('i.id = :id')
            ->andWhere('l.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
