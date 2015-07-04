<?php


$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';
$autoloader->add('Jig', [realpath(__DIR__).'/../var/compile/']);

use Amp\Artax\Client as ArtaxClient;
use ArtaxServiceBuilder\ResponseCache;
use Arya\Request;
use Arya\Response;
use Auryn\Injector;
use Jig\JigConfig;
use Jig\Jig;
use Tier\ResponseBody\HtmlBody;
use Jig\JigBase;
use Tier\Tier;
use Tier\InjectionParams;
use Tier\Data\RouteList;
use GithubService\GithubArtaxService\GithubService;
use Tier\Data\ErrorInfo;


function generateRequest() {
    $_input = empty($_SERVER['CONTENT-LENGTH']) ? NULL : fopen('php://input', 'r');

    return new Request($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_input);
}

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

        define('DEBUG', true);
        
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

    $injector->delegate('Tier\Data\PDOSQLConfig', 'createPDOSQLConfig');
    $injector->share('Tier\Data\PDOSQLConfig');

    $injector->delegate('\PDO', 'createPDO');
    $injector->share('\PDO');

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


function sendErrorResponse(Request $request, $body, $errorCode)
{
    $response = new Response();
    $response->setBody($body);
    $response->setStatus($errorCode);

    sendResponse($request, $response);
}



function sendResponse(Request $request, Response $response, $autoAddReason = true)
{
    $statusCode = $response->getStatus();
    $reason = $response->getReasonPhrase();
    if ($autoAddReason && empty($reason)) {
        $reasonConstant = "Arya\\Reason::HTTP_{$statusCode}";
        $reason = defined($reasonConstant) ? constant($reasonConstant) : '';
        $response->setReasonPhrase($reason);
    }

    $statusLine = sprintf("HTTP/%s %s", $request['SERVER_PROTOCOL'], $statusCode);
    if (isset($reason[0])) {
        $statusLine .= " {$reason}";
    }

    header($statusLine);

    foreach ($response->getAllHeaderLines() as $headerLine) {
        header($headerLine, $replace = FALSE);
    }

    flush(); // Force header output

    $body = $response->getBody();

    if (method_exists($body, '__toString')) {
        echo $body->__toString();
    }
    else if (is_string($body)) {
        echo $body;
    } 
    elseif (is_callable($body)) {
        $this->outputCallableBody($body);
    }
    else {
        //this is bad.
    }
}


