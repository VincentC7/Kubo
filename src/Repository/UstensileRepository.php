<?php

namespace App\Repository;

use App\Entity\Ustensile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ustensile>
 */
class UstensileRepository extends ServiceEntityRepository
{
    /** @var array<string, Ustensile> */
    private array $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ustensile::class);
    }

    public function findOneByNom(string $nom): ?Ustensile
    {
        return $this->findOneBy(['nom' => $nom]);
    }

    public function findOrCreate(string $nom): Ustensile
    {
        if (isset($this->cache[$nom])) {
            return $this->cache[$nom];
        }

        $ustensile = $this->findOneByNom($nom);
        if ($ustensile === null) {
            $ustensile = new Ustensile($nom);
            $this->getEntityManager()->persist($ustensile);
        }

        $this->cache[$nom] = $ustensile;

        return $ustensile;
    }

    /**
     * Vide le cache interne (à appeler après em->clear()).
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}
