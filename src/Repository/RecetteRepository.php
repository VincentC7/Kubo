<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Recette>
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

    public function findOneByUuid(string $uuid): ?Recette
    {
        try {
            $uid = Uuid::fromString($uuid);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->findOneBy(['id' => $uid]);
    }

    /**
     * Retourne une page de recettes filtrées et le total correspondant.
     *
     * Filtres supportés :
     *   - q          : recherche texte sur nom et description (ILIKE)
     *   - tag        : nom exact du tag
     *   - difficulte : valeur exacte ('Facile', 'Intermédiaire', 'Difficile')
     *   - temps_max  : tempsTotal <= N minutes
     *   - ingredient : recherche texte sur le nom de l'ingrédient (ILIKE)
     *
     * @param array{q?: string, tag?: string, difficulte?: string, temps_max?: int|string, ingredient?: string} $filters
     * @return array{items: Recette[], total: int}
     */
    public function findPaginated(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('r');

        if (!empty($filters['q'])) {
            $q = '%' . mb_strtolower($filters['q']) . '%';
            $qb->andWhere('LOWER(r.nom) LIKE :q OR LOWER(r.description) LIKE :q')
               ->setParameter('q', $q);
        }

        if (!empty($filters['tag'])) {
            $qb->innerJoin('r.tags', 'tag_filter')
               ->andWhere('tag_filter.nom = :tag')
               ->setParameter('tag', $filters['tag']);
        }

        if (!empty($filters['difficulte'])) {
            $qb->andWhere('r.difficulte = :difficulte')
               ->setParameter('difficulte', $filters['difficulte']);
        }

        if (isset($filters['temps_max']) && $filters['temps_max'] !== '') {
            $qb->andWhere('r.tempsTotal <= :temps_max')
               ->setParameter('temps_max', (int) $filters['temps_max']);
        }

        if (!empty($filters['ingredient'])) {
            $ing = '%' . mb_strtolower($filters['ingredient']) . '%';
            $qb->innerJoin('r.recetteIngredients', 'ri_filter')
               ->innerJoin('ri_filter.ingredient', 'ing_filter')
               ->andWhere('LOWER(ing_filter.nom) LIKE :ingredient')
               ->setParameter('ingredient', $ing);
        }

        // Total (clone avant pagination)
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT r.id)')
                               ->getQuery()
                               ->getSingleScalarResult();

        // Items paginés
        $items = $qb->select('r')
                    ->orderBy('r.nom', 'ASC')
                    ->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
