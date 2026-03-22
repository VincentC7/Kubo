<?php

namespace App\Dto;

/**
 * DTO de réponse pour GET /api/catalogue.
 *
 * Contient la sélection paginée de recettes de la semaine et ses métadonnées.
 * Les CATALOGUE_SIZE premières recettes du catalogue total constituent la
 * "sélection premium" (scorée). Les suivantes sont le reste du catalogue.
 */
final readonly class CatalogueDto implements \JsonSerializable
{
    /**
     * @param string               $semaine       Semaine ISO (ex: "2026-W12")
     * @param RecetteListItemDto[] $recettes       Page courante de recettes
     * @param int                  $total          Nombre total de recettes dans le catalogue
     * @param int                  $page           Numéro de page courant
     * @param int                  $limit          Nombre d'éléments par page
     * @param int                  $catalogueSize  Nombre de recettes dans la sélection premium
     */
    public function __construct(
        public string $semaine,
        public array  $recettes,
        public int    $total,
        public int    $page,
        public int    $limit,
        public int    $catalogueSize,
    ) {}

    public function jsonSerialize(): array
    {
        $pages = $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 1;

        return [
            'semaine'  => $this->semaine,
            'recettes' => $this->recettes,
            'meta'     => [
                'total'          => $this->total,
                'page'           => $this->page,
                'limit'          => $this->limit,
                'pages'          => $pages,
                'catalogue_size' => $this->catalogueSize,
            ],
        ];
    }
}
