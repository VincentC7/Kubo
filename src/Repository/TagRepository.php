<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    /** @var array<string, Tag> */
    private array $cache = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findOneByNom(string $nom): ?Tag
    {
        return $this->findOneBy(['nom' => $nom]);
    }

    public function findOrCreate(string $nom): Tag
    {
        if (isset($this->cache[$nom])) {
            return $this->cache[$nom];
        }

        $tag = $this->findOneByNom($nom);
        if ($tag === null) {
            $tag = new Tag($nom);
            $this->getEntityManager()->persist($tag);
        }

        $this->cache[$nom] = $tag;

        return $tag;
    }

    /**
     * Vide le cache interne (à appeler après em->clear()).
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}
