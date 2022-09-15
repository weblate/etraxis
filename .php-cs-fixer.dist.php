<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath(['Kernel.php', 'bootstrap.php'])
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
        'blank_line_before_statement' => [
            'statements' => [
                'break', 'continue', 'declare', 'default', 'exit', 'for', 'foreach', 'goto', 'if', 'include', 'include_once',
                'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'yield', 'yield_from',
            ],
        ],
        'declare_strict_types'        => false,
        'native_function_invocation'  => false,
        'self_static_accessor'        => true,
        'single_line_comment_spacing' => false,
    ])
    ->setFinder($finder)
;
