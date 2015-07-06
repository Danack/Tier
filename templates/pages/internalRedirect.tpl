
{extends file="component/blankPage"}

{block name='content'}
    
    
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
<p>
This is an example to show that a controller can do an internal redirect to another controller.
</p>

<p>
    The first callable does the following things:
</p>
    
<ul>
    <li>Tells the DIC that any request for a dependency of type `Tier\Model\RunTimeData` should be call the anonymous function the callable provided.</li>
    <li>Tell the application that the next thing to be called is `Tier\Controller\InternalRedirect::secondCall`</li>
</ul>
    

<p>
    The second callable does indeed need one of those objects, and so when the DIC invokes `Tier\Controller\InternalRedirect::secondCall`, it creates the object by calling the delegated function.
</p>
<p>
    The second callable then tells the application that the template internalRedirect needs to be rendered.
</p>
        </div>
    </div>

    {inject name='runTimeData' type='Tier\Model\RunTimeData'}

    <div class="row">
        <div class="col-md-12">
            
This is what was contained in the `RunTimeData` object:
        <i>"{$runTimeData->someVarThatIsDeterminedAtRuntime}"</i>
        </div>
    </div>
</div>
    
{/block}