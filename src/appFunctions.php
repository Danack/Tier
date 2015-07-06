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


function createGithubArtaxService(ArtaxClient $client, \Amp\Reactor $reactor, ResponseCache $cache) {
    return new GithubService($client, $reactor, $cache, "Danack/Tier");
}


function getEnvWithDefault($env, $default)
{
    $value = getenv($env);
    if ($value === false) {
        return $default;
    }
    return $value;
}

function createPDOSQLConfig() {    
    return new \Tier\Data\PDOSQLConfig(
        getEnvWithDefault('pdo.dsn', null),
        getEnvWithDefault('pdo.user', null),
        getEnvWithDefault('pdo.password', null)
    );
}

function createPDO(\Tier\Data\PDOSQLConfig $pdoSQLConfig)
{
    $instance = new PDO(
        $pdoSQLConfig->dsn,
        $pdoSQLConfig->user,
        $pdoSQLConfig->password
    );
    
    return $instance;
}


function createJigConfig()
{
    $jigConfig = new JigConfig(
        __DIR__."/../templates/",
        __DIR__."/../var/compile/",
        'tpl',
        \Jig\Jig::COMPILE_ALWAYS
        //Jig::COMPILE_CHECK_MTIME
    );

    return $jigConfig;
}


function createTemplateResponse(JigBase $template)
{
    $text = $template->render();

    return new HtmlBody($text);
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


function getRouteCallable(RouteList $routeList, Response $response) {

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

function routesFunction(RouteList $routeList, FastRoute\RouteCollector $r) {
    foreach ($routeList->getRoutes() as $route) {
        $r->addRoute(
            $route->method,
            $route->path,
            $route->callable
        );
    }
};


function getLastModifiedTime($timestamp) {
    return gmdate('D, d M Y H:i:s', $timestamp). ' UTC';
}

function getFileLastModifiedTime($fileNameToServe) {
    return getLastModifiedTime(filemtime($fileNameToServe));
}

