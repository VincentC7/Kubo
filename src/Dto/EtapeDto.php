<?php

namespace App\Dto;

use App\Entity\Etape;

final readonly class EtapeDto implements \JsonSerializable
{
    /**
     * @param list<string> $instructions
     */
    public function __construct(
        public int $numero,
        public array $instructions,
        public ?string $astuce,
    ) {}

    public static function fromEntity(Etape $etape): self
    {
        return new self(
            numero: $etape->getNumero(),
            instructions: $etape->getInstructions(),
            astuce: $etape->getAstuce(),
        );
    }

    public function jsonSerialize(): array
    {
        return (array) $this;
    }
}
