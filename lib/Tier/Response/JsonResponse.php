<?php


namespace Tier\Response;




class JsonResponse implements Response {

    private $data;

    function __construct(array $data) {
        $this->data = $data;
    }

    function send() {
        
        $text = json_encode($this->data, JSON_PRETTY_PRINT);
        
        if ($text === null) {
            throw new \Exception("Could not JSON encode data");
        }

        header("Content-Type: application/json");
        echo $text;
    }
}

 