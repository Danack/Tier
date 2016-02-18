<?php


/**
 * Helper function to bind the route list to FastRoute
 * @param \FastRoute\RouteCollector $r
 */
function routesFunction(FastRoute\RouteCollector $r)
{
    $r->addRoute('GET', "/", ['TierTest\Controller\BasicController', 'helloWorld']);
    $r->addRoute('GET', "/instantiateUnknownClass", ['TierTest\Controller\BasicController', 'instantiateUnknownClass']);
    $r->addRoute('GET', "/throwException", ['TierTest\Controller\BasicController', 'throwException']);
    $r->addRoute('GET', "/unknownDependency", ['TierTest\Controller\BasicController', 'unknownDependency']);
    $r->addRoute('GET', '/cleanupException', ['TierTest\Controller\BasicController', 'testOutputBufferingIsCleared']);
    $r->addRoute('GET', '/renderTemplateExecutable', ['TierTest\Controller\BasicController', 'renderTemplateExecutable']);
}
