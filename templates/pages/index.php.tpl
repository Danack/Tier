
{extends file="component/blankPage"}

{block name='content'}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
<p>
    Hello, this is an example site to show how using a Dependency Injection Container can be used to have a multi-tier application.
</p>

<ul>
    <li>
        <a href='/dependency'>Dependencies</a>
        Controllers need dependencies yo'.
    </li>
    
    <li>
        <a href='/routeParams'>Route params</a>
        
        An example with route params. This shows that the 'routing tier' that is present in most PHP frameworks is just a specialised type of tier.
    </li>
    
    <li>
        <a href='/internalRedirect'>Internal redirects</a>
        An example showing how to redirect from one callable to the next.
    </li>
    
    <li>
        <a href='/apiExample'>Direct response</a>
        For the rest of the examples the controller returns a new 'Tier' to be called. For this example a response body is returned, indicating to the application that there is no more stuff to be processed.
    </li>
    
    <li>
        <a href='/functions'>Function as a controller</a>
        It's absolutely find to use a function as the callable for a controller, which this example does.\nThe reason why it's probably still useful to use classes as the controllers is that classes can be autoloaded, whereas you can't do that with functions.
    </li>

    
    <li>
        <a href='/usesDB'>Controller depends on a database</a> Somebody asked for an example where the controller has a dependency on a DB connection. This is it - it won't work on your local machine, but it's there for people to see.</a>
    </li>
</ul>

         
            </div>
        </div>
    </div>
{/block}