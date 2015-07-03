<?php


namespace Tier\Response;



class TextResponse implements Response {

    private $text;

    function __construct($text) {
        $this->text = $text;
    }

    function send() {
        echo $this->text;
    }
}

 