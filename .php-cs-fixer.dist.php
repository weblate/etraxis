<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/migrations')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath([
        'Entity/Enums',
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([

        //--------------------------------------------------------------
        //  Rule sets
        //--------------------------------------------------------------
        '@Symfony'                    => true,
        '@Symfony:risky'              => true,
        '@PhpCsFixer'                 => true,
        '@PhpCsFixer:risky'           => true,
        '@DoctrineAnnotation'         => true,
        '@PHP80Migration'             => true,
        '@PHP80Migration:risky'       => true,
        '@PHP81Migration'             => true,
        '@PHPUnit84Migration:risky'   => true,

        //--------------------------------------------------------------
        //  Rules override
        //--------------------------------------------------------------
        'binary_operator_spaces'      => [
            'default'   => null,
            'operators' => [
                '='   => 'align',
                '+='  => 'align',
                '-='  => 'align',
                '*='  => 'align',
                '/='  => 'align',
                '%='  => 'align',
                '**=' => 'align',
                '&='  => 'align',
                '|='  => 'align',
                '^='  => 'align',
                '<<=' => 'align',
                '>>=' => 'align',
                '.='  => 'align',
                '??=' => 'align',
                '=>'  => 'align',
            ],
        ],
        'declare_strict_types'            => false,
        'native_function_invocation'      => false,
        'self_static_accessor'            => true,
        'single_line_comment_spacing'     => false,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder)
;
