<?php


namespace Tier\Model;


class BlogPost {

    public $title;
    public $text;
    
    public function __construct($title, $text)
    {
        $this->title = $title;
        $$this->text = $text;
    }
}

