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
 * @author Jáchym Toušek <enumag@gmail.com>
 */
final class CeilFunction extends FunctionNode
{
    private Node|SimpleArithmeticExpression $arithmeticExpression;

    /**
     * @see FunctionNode::parse
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->arithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @see FunctionNode::getSql
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf('CEIL(%s)', $sqlWalker->walkSimpleArithmeticExpression($this->arithmeticExpression));
    }
}
