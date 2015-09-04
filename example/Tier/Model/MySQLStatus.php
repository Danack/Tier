<?php


namespace Tier\Model;


class MySQLStatus {

    public $available;
    public $status;
    
    public function __construct($available = false, $status = 'Unknown')
    {
        $this->available = false;
        $this->status = 'Unknown';
    }

}

