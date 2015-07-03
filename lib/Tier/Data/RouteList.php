<?php



namespace Tier\Data;

class RouteList
{
    private $routes = [];
    
    public function __construct()
    {
        $fn = function ( $method, $path, $callable) {
            $this->routes[] = new Route($method, $path, $callable);
        };

        // For the example we will hard code all the things.
        $fn('GET', "/", ['Tier\Controller\Index', 'display']);
        $fn('GET', "/dependency", ['Tier\Controller\GithubAPI', 'display']);
        $fn('GET', "/internalRedirect", ['Tier\Controller\InternalRedirect', 'firstCall']);
        $fn('GET', "/apiExample", ['Tier\Controller\ApiExample', 'call']);
    }

    /**
     * @return \Tier\Data\Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

}
