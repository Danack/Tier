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
    $r->addRoute('GET', '/introduction', ['TierTest\Controller\BasicController', 'introduction']);
}

class JigFastRouterTest extends BaseTestCase
{
    /** @var  \Auryn\Injector */
    private $injector;
    
    public function setup()
    {
        parent::setup();
        $this->injector = createInjector();
    }

    public function testRoutingToIndex()
    {
        $request = new CLIRequest("/index", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\Bridge\TierJig', 'createHtmlBody'],
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
        $result = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\Bridge\TierJig', 'createHtmlBody'],
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
        $result = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['Tier\Bridge\TierJig', 'createHtmlBody'],
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
        $result = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');
        $this->assertInstanceOf('Tier\Executable', $result);
        /** @var $result \Tier\Executable */
        $this->assertEquals(
            ['TierTest\Controller\BasicController', 'introduction'],
            $result->getCallable()
        );
    }
    
    
    public function testRouting404()
    {
        $request = new CLIRequest("/thisdoesnotexist", 'example.com');
        $this->injector->alias('Psr\Http\Message\ServerRequestInterface', get_class($request));
        $this->injector->share($request);
        $result = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');

        
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
        $renderCallable = $this->injector->execute('Tier\Bridge\JigFastRouter::routeRequest');
        $body = TierApp::executeExecutable($renderCallable, $this->injector);
        $this->assertInstanceOf('Room11\HTTP\Body\TextBody', $body);
        /** @var $body \Room11\HTTP\Body\HtmlBody */
        $html = $body->getData();
        $this->assertContains("Method not allowed for route.", $html);
        $this->assertEquals(405, $body->getStatusCode());
    }
}
