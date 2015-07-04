
{extends file="component/blankPage"}

{block name='content'}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                
<p>
    This example shows how the router setting up parameter extracted from the URI is the same mechanism of DI that the rest of the application uses.
</p>

<p>
                Please click on a link: <br/>
                <a href="/routeParams/Alice">A for Alice</a><br/>
                <a href="/routeParams/Bob">B for Bob</a><br/>
                <a href="/routeParams/Charlie">C for Charlie</a><br/>
                <a href="/routeParams/Daniel">D for Daniel</a><br/>
                
</p>
            </div>
        </div>
    </div>
{/block}