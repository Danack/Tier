<?php

namespace Tier\Bridge;

use FastRoute\Dispatcher;
use Jig\JigConfig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body\TextBody;
use Tier\Bridge\RouteParams;
use Tier\Executable;
use AurynConfig\InjectionParams;

/**
 * Matches a request to a route and returns an Executable from the route's callable.
 *
 * If no route is found, tries to match the path  of the request to a template. If a
 * template is found, an executable that renders that template is returned.
 *
 * If no route is matched and no template matches a 404 response body is generated.
 * If a route is matched but with a non-allowed HTTP method a 405 response body is
 * returned.
 */
class JigFastRouter
{
    /** @var \Jig\JigConfig  */
    private $jigConfig;
    
    public function __construct(
        JigConfig $jigConfig,
        Dispatcher $dispatcher,
        TierJig $tierJig
    ) {
        $this->jigConfig = $jigConfig;
        $this->dispatcher = $dispatcher;
        $this->tierJig = $tierJig;
    }

    /**
     * Check if a template with the given name exists in the 'pages' sub-directory of the templates
     * directory.
     * @param $templateName
     * @return bool|string false if the template does not exist, otherwise the normalised name
     * of the template.
     */
    private function templateExists($templateName)
    {
        if (substr($templateName, -1) === '/') {
            $templateName .= "index";
        }
        $templateName = str_replace('..', '', $templateName);
        $templateNormalisedName = 'pages'.$templateName;
        $templatePathname = $this->jigConfig->getTemplatePath($templateNormalisedName);
        
        // Does the path match the file name of a template?
        if (file_exists($templatePathname) === true) {
            return $templateNormalisedName;
        }

        // Does the path with '/index' added match the file name of a template?
        $indexName = $templateNormalisedName."/index";
        $templatePathname = $this->jigConfig->getTemplatePath($indexName);
        if (file_exists($templatePathname) === true) {
            return $indexName;
        }
    
        return false;
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

            return new Executable($handler, $injectionParams, null);
        }
        else if ($dispatcherResult === \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            //TODO - need to embed allowedMethods....theoretically.
            return new Executable([$this, 'serve405ErrorPage']);
        }
    
        $templateName = $this->templateExists($path, $this->jigConfig);
        if ($templateName !== false) {
            return $this->tierJig->createJigExecutable($templateName);
        }
    
        return new Executable([$this, 'serve404ErrorPage']);
    }

    public function serve404ErrorPage()
    {
        return new TextBody('Route not found.', 404);
    }
    
    public function serve405ErrorPage()
    {
        return new TextBody('Method not allowed for route.', 405);
    }
}
