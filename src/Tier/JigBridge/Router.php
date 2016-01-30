<?php

namespace Tier\JigBridge;

use FastRoute\Dispatcher;
use Jig\JigConfig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tier\Executable;
use Tier\InjectionParams;
use Room11\HTTP\Body\TextBody;

class Router
{
    /** @var \Jig\JigConfig  */
    private $jigConfig;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request
     * @return Executable
     */
    public function routeRequest(Request $request)
    {
        $path = $request->getUri()->getPath();
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $path);
        $dispatcherResult = $routeInfo[0];
        
        if ($dispatcherResult === \FastRoute\Dispatcher::FOUND) {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $injectionParams = InjectionParams::fromParams($vars);
            $injectionParams->share(new \Tier\JigBridge\RouteInfo($vars));
    
            return new Executable($handler, $injectionParams, null);
        }
        else if ($dispatcherResult === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            //TODO - need to embed allowedMethods....theoretically.
            return new Executable([$this, 'serve405ErrorPage']);
        }
    
        return new Executable([$this, 'serve404ErrorPage']);
    }

    public static function serve404ErrorPage()
    {
        return new TextBody('Route not found.', 404);
    }
    
    public static function serve405ErrorPage()
    {
        return new TextBody('Method not allowed for route.', 405);
    }
}
