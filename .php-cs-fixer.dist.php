<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
  ->in(__DIR__)
  ->exclude('var')
  ->exclude('vendor')
  ->notPath(['src/Kernel.php', './Kernel.php'])
  ->ignoreDotFiles(true);

return (new PhpCsFixer\Config())
  ->setIndent('  ')
  ->setRules([
    '@Symfony' => true,
    '@PHP74Migration' => true,
    '@PHP74Migration:risky' => true,
    '@PHPUnit75Migration:risky' => true,
    '@DoctrineAnnotation' => true,
    '@PSR12' => true,
    //'@PhpCsFixer' => true,
    'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
    'concat_space' => ['spacing' => 'one'],
  ])
  ->setRiskyAllowed(true)
  ->setCacheFile('.php-cs-fixer.cache')
  ->setFinder($finder);

