<?php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom DQL function for PostgreSQL JSONB containment operator: @>
 *
 * Usage in DQL:
 *   JSONB_CONTAINS(ing.moisSaison, :json) = TRUE
 *
 * Generates:
 *   (column)::jsonb @> (:param)::jsonb
 */
class JsonbContains extends FunctionNode
{
    private Node $jsonbColumn;
    private Node $jsonValue;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->jsonbColumn = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->jsonValue = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '(%s)::jsonb @> (%s)::jsonb',
            $this->jsonbColumn->dispatch($sqlWalker),
            $this->jsonValue->dispatch($sqlWalker),
        );
    }
}
