<?php

namespace App\Repository;

use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ingredient>
 */
class IngredientRepository extends ServiceEntityRepository
{
    /** @var array<string, Ingredient> */
    private array $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }

    public function findOneByNom(string $nom): ?Ingredient
    {
        return $this->findOneBy(['nom' => $nom]);
    }

    public function findOrCreate(string $nom): Ingredient
    {
        if (isset($this->cache[$nom])) {
            return $this->cache[$nom];
        }

        $ingredient = $this->findOneByNom($nom);
        if ($ingredient === null) {
            $ingredient = new Ingredient($nom);
            $this->getEntityManager()->persist($ingredient);
        }

        $this->cache[$nom] = $ingredient;

        return $ingredient;
    }

    /**
     * Vide le cache interne (à appeler après em->clear()).
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}
