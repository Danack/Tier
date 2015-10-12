<?php

use Jig\JigConfig;
use Jig\Jig;
use Jig\JigBase;
use Tier\Tier;
use Tier\InjectionParams;
use Room11\HTTP\Body\HtmlBody;
use Room11\HTTP\Response;
use Room11\HTTP\Request;

/**
 * Read config settings from environment with a default value.
 * @param $env
 * @param $default
 * @return string
 */
function getEnvWithDefault($env, $default)
{
    $value = getenv($env);
    if ($value === false) {
        return $default;
    }
    return $value;
}


/**
 * @return JigConfig
 */
function createJigConfig()
{
    $jigConfig = new JigConfig(
        __DIR__."/./templates/",
        __DIR__."/./var/compile/",
        'tpl',
        getEnvWithDefault('jig.compile', \Jig\Jig::COMPILE_ALWAYS)
    );

    return $jigConfig;
}


/**
 * Helper function to bind the route list to FastRoute
 * @param \FastRoute\RouteCollector $r
 */
function routesFunction(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', ['Tier\Controller\Index', 'display']);
};


/**
 * The callable that routes a request.
 * @param Request $request
 * @param Response $response
 * @return Tier
 */
function routeRequest(Request $request, Response $response) {

    $dispatcher = FastRoute\simpleDispatcher('routesFunction');

    $httpMethod = 'GET'; //yay hard coding.
    $uri = '/';
    //TODO! - use Request.
    if (array_key_exists('REQUEST_URI', $_SERVER)) {
        $uri = $_SERVER['REQUEST_URI'];
    }
    
    $path = $uri;
    $queryPosition = strpos($path, '?');
    if ($queryPosition !== false) {
        $path = substr($path, 0, $queryPosition);
    }

    $routeInfo = $dispatcher->dispatch($httpMethod, $path);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND: {
            $response->setStatus(404);
            return getTemplateCallable('error/error404');
        }

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED: {
            // TODO - this is meant to set a header saying which methods
            $allowedMethods = $routeInfo[1];
            $response->setStatus(405);
            return getTemplateCallable('error/error405');
        }

        case FastRoute\Dispatcher::FOUND: {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $params = InjectionParams::fromParams($vars);

            return new Tier($handler, $params);
        }

        default: {
            //Not supported
            // TODO - this is meant to set a header saying which methods
            $response->setStatus(404);
            return getTemplateCallable('error/error404');
            break;
        }
    }
}

/**
 * @param JigBase $template
 * @return HtmlBody
 * @throws Exception
 * @throws \Jig\JigException
 */
function createHtmlBody(JigBase $template)
{
    $text = $template->render();

    return new HtmlBody($text);
}


/**
 * Helper function to allow template rendering to be easier.
 * @param $templateName
 * @param array $sharedObjects
 * @return Tier
 */
function getTemplateCallable($templateName, array $sharedObjects = [])
{
    $fn = function (Jig $jigRender) use ($templateName, $sharedObjects) {
        $className = $jigRender->getTemplateCompiledClassname($templateName);
        $jigRender->checkTemplateCompiled($templateName);

        $alias = [];
        $alias['Jig\JigBase'] = $className;
        $injectionParams = new InjectionParams($sharedObjects, $alias, [], []);

        return new Tier('createHtmlBody', $injectionParams);
    };

    return new Tier($fn);
}

/**
 * Format a time to an rfc2616 timestamp
 * @param $timestamp
 * @return string
 */
function getLastModifiedTime($timestamp) {
    return gmdate('D, d M Y H:i:s', $timestamp). ' UTC';
}

/**
 * Get the rfc2616 timestamp of a file
 * @param $fileNameToServe
 * @return string
 */
function getFileLastModifiedTime($fileNameToServe) {
    return getLastModifiedTime(filemtime($fileNameToServe));
}

