<?php

namespace TierTest\JigBridge;

use Auryn\Injector;
use TierTest\BaseTestCase;
use Jig\JigConfig;
use Jig\Jig;
use FastRoute\RouteCollector;
use Room11\HTTP\Request\CLIRequest;
use Tier\TierApp;

function createDispatcher()
{
    $dispatcher = \FastRoute\SimpleDispatcher('TierTest\JigBridge\routesFunction');

    return $dispatcher;
}

function routesFunction(\FastRoute\RouteCollector $r)
{
    $r->addRoute('GET', '/introduction', ['TierJigSkeleton\Controller\Basic', 'introduction']);
}

class RouterTest extends BaseTestCase
{    
    /** @var  \Auryn\Injector */
    private $injector;
    
    public function setup()
    {
        parent::setup();
        // Create a JigConfig object
        $jigConfig = new JigConfig(
            __DIR__."/../../fixtures/templates/", //directory the source templates are in
            __DIR__."/../../var/generatedTemplates/",//directory the generated PHP code will be written to.
            Jig::COMPILE_CHECK_MTIME// How to check if the templates need compiling.
        );

        $this->injector = new Injector();
        $this->injector->alias('Jig\Escaper', 'Jig\Bridge\ZendEscaperBridge');
        $this->injector->delegate('FastRoute\Dispatcher', 'TierTest\JigBridge\createDispatcher');
        $this->injector->share('FastRoute\Dispatcher');
        $this->injector->share($jigConfig);
    }


    public function testRoutingToIndex()
    {
        $request = new CLIRequest("/index", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\JigBridge\TierJig', 'createHtmlBody'],
            $result->getCallable()
        );

        $body = TierApp::executeExecutable($result, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\HtmlBody', $body);
        /** @var $body \Room11\HTTP\Body\HtmlBody */

        $html = $body->getData();
        $this->assertContains("This is the index template.", $html);
    }

    /**
     * Checks that a request to "/" get matched to the template named index.    
     */
    public function testRoutingToIndexByDefaultMatching()
    {
        $request = new CLIRequest("/", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\JigBridge\TierJig', 'createHtmlBody'],
            $result->getCallable()
        );

        $body = TierApp::executeExecutable($result, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\HtmlBody', $body);
        /** @var $body \Room11\HTTP\Body\HtmlBody */

        $html = $body->getData();
        $this->assertContains("This is the index template.", $html);
    }
    
    
        /**
     * Checks that a request to "/" get matched to the template named index.    
     */
    public function testRoutingToIndexByDefaultSubDirectoryMatching()
    {
        $request = new CLIRequest("/subDirectory?withSomeParam=foo", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\JigBridge\TierJig', 'createHtmlBody'],
            $result->getCallable()
        );

        $body = TierApp::executeExecutable($result, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\HtmlBody', $body);
        /** @var $body \Room11\HTTP\Body\HtmlBody */

        $html = $body->getData();
        $this->assertContains("This is the sub-directory index template.", $html);
    }

    public function testRoutingToIntroduction()
    {
        $request = new CLIRequest("/introduction", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['TierJigSkeleton\Controller\Basic', 'introduction'],
            $result->getCallable()
        );
    }
    
    
    public function testRouting404()
    {
        $request = new CLIRequest("/thisdoesnotexist", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');

        
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
        $result = $this->injector->execute('Tier\JigBridge\Router::routeRequest');

        $body = TierApp::executeExecutable($result, $this->injector);

        $this->assertInstanceOf('Room11\HTTP\Body\TextBody', $body);
        
        /** @var $body \Room11\HTTP\Body\HtmlBody */
        $body->getData();
        $html = $body->getData();
        $this->assertContains("Method not allowed for route.", $html);
        $this->assertEquals(405, $body->getStatusCode());
    }

          


}