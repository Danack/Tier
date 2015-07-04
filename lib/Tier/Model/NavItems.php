<?php


namespace Tier\Model;

class NavItems implements \IteratorAggregate  {

    private $navItems = array();

    public function __construct() {
        $this->navItems = [
            new NavItem('/', 'Home'),
            new NavItem('/dependency', 'Dependencies'),
            new NavItem('/routeParams', 'Route params'),
            new NavItem("/internalRedirect", 'Internal redirects'),
            new NavItem("/apiExample", 'Direct response'),
            new NavItem("/functions", 'Function as a controller'),
            new NavItem("/usesDB", 'Controller using DB'),
        ];
    }

    /**
     * @return \Tier\Model\NavItem[]
     */
    public function getIterator() {
       return new \ArrayIterator($this->navItems);
    }
}

 