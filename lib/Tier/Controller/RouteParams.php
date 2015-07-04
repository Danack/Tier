<?php


namespace Tier\Controller;

use \Tier\Model\User;

class RouteParams {

    public function display()
    {
        return getTemplateCallable('pages/paramsForm');
    }
    
    public function displayName($username)
    {
        $user = new User($username);

        return getTemplateCallable('pages/paramsUsername', ['Tier\Model\User' => $user]);
    }
}

