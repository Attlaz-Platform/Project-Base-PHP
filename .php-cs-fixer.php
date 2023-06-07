<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return (new PhpCsFixer\Config)
    ->setRules([
        '@PSR12' => true
    ])
    ->setUsingCache(false)
    ->setFinder($finder);

