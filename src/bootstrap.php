<?php


$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';
$autoloader->add('Jig', [realpath(__DIR__).'/../var/compile/']);

use Auryn\Injector;
use Jig\JigConfig;
use Jig\Jig;
use Tier\Response\StandardHTTPResponse;
use Tier\Response\TextResponse;
use Jig\JigBase;
use Tier\Tier;
use Tier\InjectionParams;
use Tier\Data\RouteList;
use Amp\Artax\Client as ArtaxClient;
use ArtaxServiceBuilder\ResponseCache;
use GithubService\GithubArtaxService\GithubService;

/**
 * Parse errors cannot be handled inside the same file where they originate.
 * For this reason we have to include the application file externally here
 * so that our shutdown function can handle E_PARSE.
 */
register_shutdown_function(function() {
    $fatals = [
        E_ERROR,
        E_PARSE,
        E_USER_ERROR,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING
    ];

    $lastError = error_get_last();

    if ($lastError && in_array($lastError['type'], $fatals)) {
        if (headers_sent()) {
            return;
        }

        header_remove();
        header("HTTP/1.0 500 Internal Server Error");

        if (DEBUG) {
            extract($lastError);
            $msg = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);
        } else {
            $msg = "Oops! Something went terribly wrong :(";
        }

        $msg = "<pre style=\"color:red;\">{$msg}</pre>";

        echo "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
    }
});


function createGithubArtaxService(ArtaxClient $client, \Amp\Reactor $reactor, ResponseCache $cache) {

    return new GithubService($client, $reactor, $cache, "Danack/Tier");
}



function bootstrapInjector() {

    $jigConfig = new JigConfig(
        __DIR__."/../templates/",
        __DIR__."/../var/compile/",
        'php.tpl',
        \Jig\Jig::COMPILE_ALWAYS
        //Jig::COMPILE_CHECK_MTIME
    );

    $injector = new Injector();

    $injector->share($jigConfig);
    $injector->share('Jig\JigRender');
    $injector->share('Jig\Jig');
    $injector->share('Jig\JigConverter');
    
    $injector->delegate('Amp\Reactor', 'Amp\getReactor');

    

    $injector->share('Amp\Reactor');

    $injector->alias(
        'ArtaxServiceBuilder\ResponseCache',
        'ArtaxServiceBuilder\ResponseCache\NullResponseCache'
    );

    $injector->delegate(
        'GithubService\GithubArtaxService\GithubService',
        'createGithubArtaxService'
    );

      
    
    $injector->share($injector); //yolo service locator

    return $injector;
}




////$reactor = \Amp\getReactor();
////$cache = new NullResponseCache();
//$client = new ArtaxClient($reactor);
////$client->setOption(ArtaxClient::OP_MS_CONNECT_TIMEOUT, 5000);
////$client->setOption(ArtaxClient::OP_MS_KEEP_ALIVE_TIMEOUT, 1000);
////$githubAPI = new GithubArtaxService($client, $reactor, $cache, "Danack/test");



function createTemplateResponse(JigBase $template)
{
    $text = $template->render();

    return new TextResponse($text);
}

function getTemplateCallable($templateName, array $sharedObjects = [])
{
    $fn = function (Jig $jigRender) use ($templateName, $sharedObjects) {
        $className = $jigRender->getTemplateCompiledClassname($templateName);
        $jigRender->checkTemplateCompiled($templateName);

        $alias = [];
        $alias['Jig\JigBase'] = $className;
        $injectionParams = new InjectionParams($sharedObjects, $alias, [], []);

        return new Tier('createTemplateResponse', $injectionParams);
    };

    return new Tier($fn);
}


function getRouteCallable(RouteList $routeList) {

    $fn = function (FastRoute\RouteCollector $r) use ($routeList) {
        routesFunction($routeList, $r);
    };
    
    
    $dispatcher = FastRoute\simpleDispatcher($fn);

    $httpMethod = 'GET'; //yay hard coding.
    $uri = '/';

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
            return new StandardHTTPResponse(404, $uri, "Not found");
        }

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED: {
            $allowedMethods = $routeInfo[1];
            // ... 405 Method Not Allowed
            return new StandardHTTPResponse(405, $uri, "Not allowed");
        }

        case FastRoute\Dispatcher::FOUND: {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $params = InjectionParams::fromParams($vars);
            
            return new Tier($handler, $params);
        }
            
        default: {
            //Not supported
            return new StandardHTTPResponse(404, $uri, "Not found");
            break;
        }
    }
}


function routesFunction(RouteList $routeList, FastRoute\RouteCollector $r) {

    foreach ($routeList->getRoutes() as $route) {
        $r->addRoute(
            $route->method,
            $route->path,
            $route->callable
        );
    }
};


function addInjectionParams(Injector $injector, Tier $tier)
{
    $injectionParams = $tier->getInjectionParams();
    
    if (!$injectionParams) {
        return;
    }
        
    foreach ($injectionParams->getAliases() as $original => $alias) {
        $injector->alias($original, $alias);
    }
    
    foreach ($injectionParams->getShares() as $share) {
        $injector->share($share);
    }
    
    foreach ($injectionParams->getParams() as $paramName => $value) {
        $injector->defineParam($paramName, $value);
    }
    
    foreach ($injectionParams->getDelegates() as $className => $callable) {
        $injector->delegate($className, $callable);
    }
}

function getLastModifiedTime($timestamp) {
    return gmdate('D, d M Y H:i:s', $timestamp). ' UTC';
}

function getFileLastModifiedTime($fileNameToServe) {
    return getLastModifiedTime(filemtime($fileNameToServe));
}