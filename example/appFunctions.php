<?php

use Amp\Artax\Client as ArtaxClient;
use ArtaxServiceBuilder\ResponseCache;
use Arya\Response;
use Jig\JigConfig;
use Jig\Jig;
use Tier\ResponseBody\HtmlBody;
use Jig\JigBase;
use Tier\Tier;
use Tier\InjectionParams;
use Tier\Data\RouteList;
use GithubService\GithubArtaxService\GithubService;
use Tier\Data\ErrorInfo;


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
 * @return \Tier\Data\PDOSQLConfig
 */
function createPDOSQLConfig() {    
    return new \Tier\Data\PDOSQLConfig(
        getEnvWithDefault('pdo.dsn', null),
        getEnvWithDefault('pdo.user', null),
        getEnvWithDefault('pdo.password', null)
    );
}

/**
 * @param \Tier\Data\PDOSQLConfig $pdoSQLConfig
 * @return PDO
 */
function createPDO(\Tier\Data\PDOSQLConfig $pdoSQLConfig)
{
    $instance = new PDO(
        $pdoSQLConfig->dsn,
        $pdoSQLConfig->user,
        $pdoSQLConfig->password
    );
    
    return $instance;
}

/**
 * @return JigConfig
 */
function createJigConfig()
{
    $jigConfig = new JigConfig(
        __DIR__."/../templates/",
        __DIR__."/../var/compile/",
        'tpl',
        getEnvWithDefault('jig.compile', \Jig\Jig::COMPILE_ALWAYS)
    );

    return $jigConfig;
}

/**
 * An example to show that 'just' a function can be the end point of a route.
 * @param GithubService $githubService
 * @return Tier
 */
function controllerAsFunction(GithubService $githubService)
{
    try {
        $repoCommitsCommand = $githubService->listRepoCommits(null, 'danack', 'imagick-demos');
        $repoCommitsCommand->setPerPage(10);
        $commits = $repoCommitsCommand->execute();

        return getTemplateCallable('pages/commits', ['GithubService\Model\Commits' => $commits]);   
    }
    catch (\Exception $e) {
        $errorInfo = new ErrorInfo(
            "Error getting commits",
            $e->getMessage()
        );

        return getTemplateCallable('pages/error', ['Tier\Data\ErrorInfo' => $errorInfo]);
    }
}


/**
 * @param ArtaxClient $client
 * @param \Amp\Reactor $reactor
 * @param ResponseCache $cache
 * @return GithubService
 */
function createGithubArtaxService(ArtaxClient $client, \Amp\Reactor $reactor, ResponseCache $cache)
{
    return new GithubService($client, $reactor, $cache, "Danack/Tier");
}

/**
 * Helper function to bind the route list to FastRoute
 * @param RouteList $routeList
 * @param \FastRoute\RouteCollector $r
 */
function routesFunction(RouteList $routeList, FastRoute\RouteCollector $r) {
    foreach ($routeList->getRoutes() as $route) {
        $r->addRoute(
            $route->method,
            $route->path,
            $route->callable
        );
    }
};


/**
 * The callable that routes a request.
 * @param RouteList $routeList
 * @param Response $response
 * @return Tier
 */
function routeRequest(RouteList $routeList, Response $response) {

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
        $className = $jigRender->getFQCNFromTemplateName($templateName);
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

