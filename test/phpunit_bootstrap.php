<?php

$autoloader = require(__DIR__.'/../vendor/autoload.php');

$autoloader->add('JigTest', [realpath('./').'/test/']);
$autoloader->add(
    "Jig\\PHPCompiledTemplate",
    [realpath(realpath('./').'/tmp/generatedTemplates/')]
);


$autoloader->add('Fixtures', [__DIR__.'/fixtures/']);
