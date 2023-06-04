<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2010-2020 Benjamin Eberlei and individual contributors
//
//  This file is part of DoctrineExtensions.
//
//  The original library is licensed under the 3-Clause BSD License,
//  see <https://opensource.org/licenses/BSD-3-Clause> for details.
//
//----------------------------------------------------------------------

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * @author Vas N <phpvas@gmail.com>
 * @author Güven Atbakan <guven@atbakan.com>
 * @author Leonardo B Motyczka <leomoty@gmail.com>
 */
final class GreatestFunction extends FunctionNode
{
    private Node $field;

    /** @var Node[] */
    private array $values = [];

    /**
     * @see FunctionNode::parse
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();

        while (count($this->values) < 1 || Lexer::T_CLOSE_PARENTHESIS !== $lexer->lookahead->type) {
            $parser->match(Lexer::T_COMMA);
            $this->values[] = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @see FunctionNode::getSql
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'GREATEST(%s, %s)',
            $this->field->dispatch($sqlWalker),
            implode(', ', array_map(fn (Node $value) => $value->dispatch($sqlWalker), $this->values))
        );
    }
}
