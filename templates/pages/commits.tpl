{extends file="component/blankPage"}

{block name='content'}
<div class="container-fluid">

    <div class="row">
        <div class="col-md-12">
            <p>
For this example, the controller has a dependency on my <a href="https://github.com/Danack/GithubArtaxService">Github API</a>. The controller then uses that dependency to get some information from Github and shares it through the DIC as the shared type `GithubService\Model\Commits`.
            
</p>
<p>
    The template then lists `GithubService\Model\Commits` as a depdendency, and the DIC injectst the shared instance when the template is rendered.
</p>
<p>
    If there is an error, then instead the template pages/error is rendered. 
</p>

        </div>
    </div>

<div class="row">
        <div class="col-md-12" style="height: 50px">
            &nbsp;
        </div>
</div>
    
<div class="row">
    <div class="col-md-12">

{inject name='commmits' type='GithubService\Model\Commits'}
    <table style="font-size: 12px">
        <thead>
            <tr>
                <th>
                    Sha
                </th>
                <th>
                    <!-- Date --> 
                </th>
                <th>
                    Message
                </th>
            </tr>
        </thead>
    
        <tbody>
    {foreach $commmits as $commit}
        <tr>

            <td>
                <a href="{$commit->commitInfo->treeURL}" target="__blank">
                {$commit->sha}
                </a>
            </td>
            <td>
                {* $commit->commitInfo->committerDate *}
            </td>
            <td>
                {$commit->commitInfo->message}
            </td>
        </tr>
    {/foreach}
    </tbody>
    </table>
        </div>
    </div>
</div>
{/block}

 