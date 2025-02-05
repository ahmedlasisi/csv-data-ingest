<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class DatediffFunction extends FunctionNode
{
    private $firstDate;
    private $secondDate;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->firstDate = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->secondDate = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'DATEDIFF(%s, %s)',
            $this->firstDate->dispatch($sqlWalker),
            $this->secondDate->dispatch($sqlWalker)
        );
    }
}
