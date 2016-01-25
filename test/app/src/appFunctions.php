<?php

function createDispatcher()
{
    $dispatcher = FastRoute\simpleDispatcher('routesFunction');
    
    return $dispatcher;
}
