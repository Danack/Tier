<?php

if (php_sapi_name() === "cli-server") {
    if (strrpos($_SERVER['PHP_SELF'], '.css') === (strlen($_SERVER['PHP_SELF']) - strlen('.css')) ||
        strrpos($_SERVER['PHP_SELF'], '.js') === (strlen($_SERVER['PHP_SELF']) - strlen('.js'))) {
        //If the requests are for static files, tell the PHP server to
        //serve them directly
        return false;
    }
}

require_once "../src/index.php";
