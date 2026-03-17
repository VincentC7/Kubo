<?php

namespace App\Dto;

use App\Entity\NutritionFait;

final readonly class NutritionDto implements \JsonSerializable
{
    public function __construct(
        public string $contexte,
        public ?float $energie_kj,
        public ?float $energie_kcal,
        public ?float $matieres_grasses,
        public ?float $acides_gras_satures,
        public ?float $glucides,
        public ?float $sucres,
        public ?float $fibres,
        public ?float $proteines,
        public ?float $sel,
        public ?float $potassium,
        public ?float $calcium,
        public ?float $cholesterol,
        public ?float $fer,
        public ?float $acides_gras_trans,
    ) {}

    public static function fromEntity(NutritionFait $nf): self
    {
        return new self(
            contexte: $nf->getContexte(),
            energie_kj: $nf->getEnergieKj(),
            energie_kcal: $nf->getEnergieKcal(),
            matieres_grasses: $nf->getMatieresGrasses(),
            acides_gras_satures: $nf->getAcidesGrasSatures(),
            glucides: $nf->getGlucides(),
            sucres: $nf->getSucres(),
            fibres: $nf->getFibres(),
            proteines: $nf->getProteines(),
            sel: $nf->getSel(),
            potassium: $nf->getPotassium(),
            calcium: $nf->getCalcium(),
            cholesterol: $nf->getCholesterol(),
            fer: $nf->getFer(),
            acides_gras_trans: $nf->getAcidesGrasTrans(),
        );
    }

    public function jsonSerialize(): array
    {
        return (array) $this;
    }
}
