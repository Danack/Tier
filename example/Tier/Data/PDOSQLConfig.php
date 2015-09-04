<?php


namespace Tier\Data;




class PDOSQLConfig {

    public $dsn;
    public $user;
    public $password;

    public function __construct(
        $dsn,
        $user,
        $password)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
    }
}


