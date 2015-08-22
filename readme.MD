
# Tier - a layered application architecture.

This is an example of how to use a DIC with multiple 'tiers' inside an application, to allow complete separation between routing, controllers and view tiers.

The whole of the dispatch of the 'app' is inside /src/app.php and some bootstrapping is done in /src/bootstrap.php


# How to run

In the /example/public directory run with the built-in webserver:

php -S 0.0.0.0:8080 index.php

or 

php -S 127.0.0.1:8080 index.php


