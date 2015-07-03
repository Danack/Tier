<?php


namespace Tier\Response;


class RedirectResponse implements Response {

    private $URL;
    private $delay;

    private $headers = [];

    function __construct($URL, $delay = 0) {
        
        $this->URL = $URL;
        $this->delay = $delay;
     //   $this->setHeader("Location", $URL);
    }

    function setHeader($type, $value) {
        $this->headers[$type] = $value;
    }

    function send() {
        
        if ($this->delay) {
            usleep($this->delay);
        }

        header("Location: ".$this->URL , null, 307);

        foreach ($this->headers as $type => $value) {
            header("$type: $value");
        }
    }
}




 