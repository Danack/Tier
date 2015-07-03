<?php


namespace Tier\Model;


class NavItem {

    public $url;
    public $description;
    public $isActive;

    public function __construct($url, $description, $isActive = false) {
        $this->url = $url;
        $this->description = $description;
        $this->isActive = $isActive;
    }
}

 