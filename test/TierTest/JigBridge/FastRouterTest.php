<?php

namespace TierTest\JigBridge;

use Auryn\Injector;
use TierTest\BaseTestCase;
use Jig\JigConfig;
use Jig\Jig;
use FastRoute\RouteCollector;
use Room11\HTTP\Request\CLIRequest;
use Tier\TierApp;
use Room11\HTTP\Body\TextBody;

//function createFastRouteDispatcher()
//{
//    $dispatcher = \FastRoute\SimpleDispatcher('TierTest\JigBridge\routesFunction');
//
//    return $dispatcher;
//}
//
//function routesFastRouteFunction(\FastRoute\RouteCollector $r)
//{
//    $r->addRoute('GET', '/introduction', ['TierTest\Controller\BasicController', 'introduction']);
//}

class FastRouterTest extends BaseTestCase
{
    /** @var  \Auryn\Injector */
    private $injector;
    
    public function setup()
    {
        parent::setup();
        $this->injector = createInjector();
    }

//    public function testRoutingToIndex()
//    {
//        $request = new CLIRequest("/index", 'example.com');
//        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
//        $this->injector->share($request);
//        $result = $this->injector->execute('Tier\Bridge\FastRouter::routeRequest');
//        $this->assertInstanceOf('Tier\Executable', $result);
//        
//        /** @var $result \Tier\Executable */
//        $this->assertEquals(
//            ['Tier\Bridge\TierJig', 'createHtmlBody'],
//            $result->getCallable()
//        );
//
//        $body = TierApp::executeExecutable($result, $this->injector);
//        $this->assertInstanceOf('Room11\HTTP\Body\HtmlBody', $body);
//        /** @var $body \Room11\HTTP\Body\HtmlBody */
//
//        $html = $body->getData();
//        $this->assertContains("This is the index template.", $html);
//    }
    
    public function testRouting404()
    {
        $request = new CLIRequest("/thisdoesnotexist", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $router = $this->injector->make('Tier\Bridge\FastRouter');
        
        $fn404ErrorPage = function()  {
            return new TextBody("Route not found.", 404);
        };
        $fn405ErrorPage = function() {
            return new TextBody("Method not allowed for route.", 405);
        };

        $result = $router->routeRequest(
            $request,
            $fn404ErrorPage,
            $fn405ErrorPage
        );

        $body = TierApp::executeExecutable($result, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\TextBody', $body);
        
        /** @var $body \Room11\HTTP\Body\HtmlBody */
        $body->getData();
        $html = $body->getData();
        $this->assertContains("Route not found.", $html);
        $this->assertEquals(404, $body->getStatusCode());
    }

    public function testRouting405()
    {
        $request = new CLIRequest("/introduction", 'example.com', 'POST');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);

        $router = $this->injector->make('Tier\Bridge\FastRouter');
        $fn404ErrorPage = function()  {
            return new TextBody("Route not found.", 404);
        };
        $fn405ErrorPage = function() {
            return new TextBody("Method not allowed for route.", 405);
        };
        
        $result = $router->routeRequest(
            $request,
            $fn404ErrorPage,
            $fn405ErrorPage
        );

        $body = TierApp::executeExecutable($result, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\TextBody', $body);
        /** @var $body \Room11\HTTP\Body\HtmlBody */
        $html = $body->getData();
        $this->assertContains("Method not allowed for route.", $html);
        $this->assertEquals(405, $body->getStatusCode());
    }
}
