<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryItem>
 */
class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function findOneByIdAndUser(string $id, User $user): ?InventoryItem
    {
        return $this->createQueryBuilder('i')
            ->where('i.id = :id')
            ->andWhere('i.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return InventoryItem[] */
    public function findByUser(User $user, ?string $category = null, bool $expiringSoon = false): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'ASC');

        if ($category !== null) {
            $qb->andWhere('i.category = :category')->setParameter('category', $category);
        }

        if ($expiringSoon) {
            $limit = new \DateTimeImmutable('+3 days');
            $today = new \DateTimeImmutable('today');
            $qb->andWhere('i.expiresAt IS NOT NULL')
               ->andWhere('i.expiresAt >= :today')
               ->andWhere('i.expiresAt <= :limit')
               ->setParameter('today', $today)
               ->setParameter('limit', $limit);
        }

        return $qb->getQuery()->getResult();
    }
}
