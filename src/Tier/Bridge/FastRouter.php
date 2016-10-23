<?php

namespace Tier\Bridge;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body\TextBody;
use Tier\Executable;
use Tier\TierHTTPApp;
use AurynConfig\InjectionParams;
use Tier\Bridge\RouteParams;
use Tier\Exception\RouteNotMatchedException;
use Tier\Exception\MethodNotAllowedException;

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
    public function routeRequest(
        Request $request,
        $fn404ErrorPage = null,
        $fn405ErrorPage = null
    ) {
        $path = $request->getUri()->getPath();
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $path);
        $dispatcherResult = $routeInfo[0];
        
        if ($dispatcherResult === Dispatcher::FOUND) {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            // Share the params once as parameters so they can
            // be injected by name
            $injectionParams = InjectionParams::fromParams($vars);
            // and then share them as a type
            $injectionParams->share(new RouteParams($vars));

            $executable = new Executable($handler, $injectionParams, null);

            return $executable;
        }
        else if ($dispatcherResult === Dispatcher::METHOD_NOT_ALLOWED) {
            if ($fn405ErrorPage === null) {
                $message = sprintf(
                    "Method '%s' not allowed for path '%s'",
                    $request->getMethod(),
                    $path
                );
                throw new MethodNotAllowedException($message);
            }

            $executable = new Executable($fn405ErrorPage);

            return $executable;
        }
        
        if ($fn404ErrorPage === null) {
            throw new RouteNotMatchedException("Route not matched for path $path");
        }

        $executable = new Executable($fn404ErrorPage);

        return $executable;
    }
}
