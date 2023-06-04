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
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * @author lordjancso <github.com/lordjancso>
 * @author Artjoms Nemiro <github.com/LinMAD>
 */
final class CastFunction extends FunctionNode
{
    private Node|SimpleArithmeticExpression $fieldIdentifierExpression;
    private string $castingTypeExpression;

    /**
     * @see FunctionNode::parse
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldIdentifierExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_AS);
        $parser->match(Lexer::T_IDENTIFIER);

        $type = $parser->getLexer()->token->value;

        if ($parser->getLexer()->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $parser->match(Lexer::T_OPEN_PARENTHESIS);

            $parameter  = $parser->Literal();
            $parameters = [$parameter->value];

            while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
                $parameter    = $parser->Literal();
                $parameters[] = $parameter->value;
            }

            $parser->match(Lexer::T_CLOSE_PARENTHESIS);
            $type .= sprintf('(%s)', implode(', ', $parameters));
        }

        $this->castingTypeExpression = $type;

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @see FunctionNode::getSql
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'CAST(%s AS %s)',
            $sqlWalker->walkSimpleArithmeticExpression($this->fieldIdentifierExpression),
            $this->castingTypeExpression
        );
    }
}
