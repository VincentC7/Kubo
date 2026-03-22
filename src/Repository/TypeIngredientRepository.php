<?php

namespace App\Repository;

use App\Entity\TypeIngredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeIngredient>
 */
class TypeIngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeIngredient::class);
    }

    public function findBySlug(string $slug): ?TypeIngredient
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Retourne tous les types indexés par slug pour un accès O(1).
     *
     * @return array<string, TypeIngredient>
     */
    public function findAllIndexedBySlug(): array
    {
        $result = [];
        foreach ($this->findAll() as $type) {
            $result[$type->getSlug()] = $type;
        }

        return $result;
    }
}
