<?php

use Auryn\Injector;
use TierTest\BaseTestCase;
use Jig\JigConfig;
use Jig\Jig;

$autoloader = require(__DIR__.'/../vendor/autoload.php');

$autoloader->add('JigTest', [realpath('./').'/test/']);
$autoloader->add(
    "Jig\\PHPCompiledTemplate",
    [realpath(realpath('./').'/tmp/generatedTemplates/')]
);

$autoloader->add('Fixtures', [__DIR__.'/fixtures/']);



function createInjector()
{
    $jigTemplatePath = new \Jig\JigTemplatePath(__DIR__."/fixtures/templates/");
    $jigCompilePath = new \Jig\JigCompilePath(__DIR__."/var/generatedTemplates/");

    // Create a JigConfig object
    $jigConfig = new JigConfig(
        $jigTemplatePath, //directory the source templates are in
        $jigCompilePath, //directory the generated PHP code will be written to.
        Jig::COMPILE_CHECK_MTIME // How to check if the templates need compiling.
    );

    $injector = new Injector();
    $injector->alias('Jig\Escaper', 'Jig\Bridge\ZendEscaperBridge');
    $injector->delegate('FastRoute\Dispatcher', 'TierTest\JigBridge\createDispatcher');
    $injector->share('FastRoute\Dispatcher');
    $injector->share($jigConfig);

    return $injector;
}
