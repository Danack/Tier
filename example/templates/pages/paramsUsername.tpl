

{extends file="component/blankPage"}

{inject name='user' type='Tier\Model\User'}

{block name='content'}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                The name in the URI is '{$user->name}'.
            </div>
        </div>
    </div>
{/block}