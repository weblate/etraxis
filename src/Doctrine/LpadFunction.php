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
 * @author Giulia Santoiemma <giuliaries@gmail.com>
 */
final class LpadFunction extends FunctionNode
{
    private Node $string;
    private Node $length;
    private Node $padstring;

    /**
     * @see FunctionNode::parse
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->length = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->padstring = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @see FunctionNode::getSql
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'LPAD(%s, %s, %s)',
            $this->string->dispatch($sqlWalker),
            $this->length->dispatch($sqlWalker),
            $this->padstring->dispatch($sqlWalker)
        );
    }
}
