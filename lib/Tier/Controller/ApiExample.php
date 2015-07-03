<?php

namespace Tier\Controller;

use Tier\Response\JsonResponse;

class ApiExample {
    public function call()
    {
        $data = [];
        $data['message'] = "This controller just returns a response directly. No more processing is required, so there is no need to return a new callable.";
        
        $data['foo'] = '123';
        $data['bar'] = '456';
        $data['instruction'] = 'Please press back on your browser to return to the previous page.';

        return new JsonResponse($data);
    }
}

