<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('Documentation')
    ->notPath('#Enum#')
    ->in(__DIR__)
;

$rules = [
    '@PSR2' => true,
    '@PSR12' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,
    '@PhpCsFixer' => true,
    '@PHP80Migration' => true,
    '@PHP80Migration:risky' => true,
    '@PHPUnit84Migration:risky' => true,
    'phpdoc_line_span' => ['const' => 'single', 'property' => 'single', 'method' => 'single'],
    'date_time_immutable' => true,
    'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
    'php_unit_test_case_static_method_calls' => false,
    'php_unit_test_class_requires_covers' => false,
    'php_unit_internal_class' => false,
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder)
;
