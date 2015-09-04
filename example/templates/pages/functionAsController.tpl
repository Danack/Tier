

{extends file="component/blankPage"}


{block name='content'}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
<p>
Auryn is capable of executing any callable which include plain old functions. And it still does the dependency injection for them.</p>

<p>
    For this example the controller is just the function 
    
</p>
                
{inject name='runtimeData' type='Tier\Model\MySQLStatus'}
<p>
    
    
    
                {$runtimeData->someVarThatIsDeterminedAtRuntime}
</p>
                
            </div>
        </div>
    </div>
{/block}