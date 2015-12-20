<?php

namespace Tier\JigBridge;

use FastRoute\Dispatcher;
use Jig\JigConfig;
use Room11\HTTP\Request;
use Tier\Executable;
use Tier\InjectionParams;
use Tier\JigBridge\TierJig;
use Room11\HTTP\Body\TextBody;

class Router
{
    /** @var \Jig\JigConfig  */
    private $jigConfig;
    
    public function __construct(
        JigConfig $jigConfig,
        Dispatcher $dispatcher,
        TierJig $tierJig,
        JigConfig $jigConfig
    ) {
        $this->jigConfig = $jigConfig;
        $this->dispatcher = $dispatcher;
        $this->tierJig = $tierJig;
        $this->jigConfig = $jigConfig;
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
        if (substr($templateName, -1) == '/') {
            $templateName .= "index";
        }
        $templateName = str_replace('..', '', $templateName);
        $templateNormalisedName = 'pages'.$templateName;
        $templatePathname = $this->jigConfig->getTemplatePath($templateNormalisedName);
     
        if (file_exists($templatePathname) == true) {
            return $templateNormalisedName;
        }

        $indexName = $templateNormalisedName."/index";

        $templatePathname = $this->jigConfig->getTemplatePath($indexName);
        if (file_exists($templatePathname) == true) {
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
        $path = $request->getPath();
    
        $queryPosition = strpos($path, '?');
        if ($queryPosition !== false) {
            $path = substr($path, 0, $queryPosition);
        }
    
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $path);
        $dispatcherResult = $routeInfo[0];
        
        if ($dispatcherResult == \FastRoute\Dispatcher::FOUND) {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $params = InjectionParams::fromParams($vars);
    
            return new Executable($handler, $params, null);
        }
        else if ($dispatcherResult == \FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            //TODO - need to embed allowedMethods....theoretically.
            return new Executable([$this, 'serve405ErrorPage']);
        }
    
        $templateName = $this->templateExists($path, $this->jigConfig);
        if ($templateName != false) {
            return $this->tierJig->createJigExecutable($templateName);
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
