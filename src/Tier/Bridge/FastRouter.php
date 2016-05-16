<?php

namespace Tier\Bridge;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body\TextBody;
use Tier\Executable;
use Tier\TierHTTPApp;
use Tier\InjectionParams;
use Tier\Bridge\RouteParams;

/**
 * Matches a request to a route and returns an Executable from the route's callable.
 * If there is no route matched or if the method is not allowed an appropriate 404/405
 * response body is generated.
 */
class FastRouter
{
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
            // Share the params once as parameters so they can
            // be injected by name
            $injectionParams = InjectionParams::fromParams($vars);
            // and then share them as a type
            $injectionParams->share(new \Tier\Bridge\RouteParams($vars));

            $executable = new Executable($handler, $injectionParams, null);
            $executable->setTierNumber(TierHTTPApp::TIER_GENERATE_BODY);

            return $executable;
        }
        else if ($dispatcherResult === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            //TODO - need to embed allowedMethods....theoretically.
            $executable = new Executable([$this, 'serve405ErrorPage']);
            $executable->setTierNumber(TierHTTPApp::TIER_GENERATE_BODY);

            return $executable;
        }

        $executable = new Executable([$this, 'serve404ErrorPage']);
        $executable->setTierNumber(TierHTTPApp::TIER_GENERATE_BODY);

        return $executable;
    }
}
