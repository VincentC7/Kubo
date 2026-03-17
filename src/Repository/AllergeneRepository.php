<?php

namespace App\Repository;

use App\Entity\Allergene;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Allergene>
 */
class AllergeneRepository extends ServiceEntityRepository
{
    /** @var array<string, Allergene> */
    private array $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Allergene::class);
    }

    public function findOneByNom(string $nom): ?Allergene
    {
        return $this->findOneBy(['nom' => $nom]);
    }

    public function findOrCreate(string $nom): Allergene
    {
        if (isset($this->cache[$nom])) {
            return $this->cache[$nom];
        }

        $allergene = $this->findOneByNom($nom);
        if ($allergene === null) {
            $allergene = new Allergene($nom);
            $this->getEntityManager()->persist($allergene);
        }

        $this->cache[$nom] = $allergene;

        return $allergene;
    }

    /**
     * Vide le cache interne (à appeler après em->clear()).
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}
