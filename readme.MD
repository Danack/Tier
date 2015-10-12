
# Tier - a layered application architecture.

Tier is an application that is built around allowing multiple 'tiers' inside an application, to allow complete separation between routing, controllers and view tiers.

For those of you who just want to look at some code, the whole of the dispatching of the application is inside ./src/TierApp.php, and the example of how to call it is in ./exmple/index.php

Note - Tier is not a 'framework' in the traditional PHP meaning - it does not support adding functionality via plugins, and modules cannot be referenced by name. Instead everything is tied together with plain old programming.


# Why?

Most application frameworks in PHP ignore a very fundamental problem; that it is not possible to know at the start of the application all of the information that is needed to run the application.

Ignoring this problem means that the other frameworks need to use a service locator or other 'lookup' facility in the middle of the app to determine how to run the application.

Tier solves this problem by:

* Explicitly enabling users to have multiple tiers of execution.

* Making adding information to the DIC be done in a `reasonble` (i.e. easy to reason about) way.

For example, in a normal web application you should want to have the following tiers

* Routing - identifies the route being called and extracts parameters from the URI and give those paramters to the DIC

* Controller - does any work required and identifies what view/template should be displayed. Puts the result of the work into the DIC so that it can be used by the view/template.

* Display - displays a webpage and returns a Body to Tier, which indicates that there are no more tiers to execute. 

# How

Each tier that needs to be executed has the following elements

* The callable to call by Tier
* Any new information to be added to the DIC which are encapsulated in the class \Tier\InjectionParams 
* An optional setup callable that should be called before the main callable is called. 

For the example application which is displying a web-page the initial tier is created by:

    $tier = new Tier('routeRequest');
    
i.e. it creates a tier which just calls the 'routeRequest' function. If a route is matched, that function returns a new tier which has the appropriate callable for the route, and any parameters that have been extracted from the URI are set in the InjectionParams.

Execution continues until either a valid body is returned to the TierApp (at which point it is sent and the request is ended) or there are no more tiers to execute.


# Running the example

There is a very, very simple example of how to use Tier in the example directory.

To run it, go to /example/public directory in the command line and run PHP's built-in webserver:

`php -S 0.0.0.0:8080 index.php`

or 

`php -S 127.0.0.1:8080 index.php`

The examples use the [Jig templating library](https://github.com/danack/jig) as that allows proper dependency injection to be used when displaying the view/template.
