<?php

use Jig\Jig;
use Jig\JigTemplatePath;
use Jig\JigCompilePath;
use Jig\JigConfig;

function createDispatcher()
{
    $dispatcher = FastRoute\simpleDispatcher('routesFunction');
    
    return $dispatcher;
}

function createJigConfig(JigTemplatePath $jigTemplatePath, JigCompilePath $jigCompilePath)
{
    return new JigConfig(
        $jigTemplatePath,
        $jigCompilePath,
        Jig::COMPILE_CHECK_MTIME
    );
}
