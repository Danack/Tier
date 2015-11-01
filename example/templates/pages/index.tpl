
{extends file="component/blankPage"}

{block name='content'}
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <p>
    Hello, this is an example site to show how using a Dependency Injection Container can be used to have a multi-tier application.
        </p>

{inject name='colorList' type='Tier\Model\ColorList'}

        <ul>
    {foreach $colorList->getColors() as $color}
          <li>
            <span style="color: {$color->getHexColor()}">{$color->getName()}</span>
          </li>
    {/foreach}
        </ul>

        <div>
    For more complete examples and documentation please go to <a href="http://tier.phpjig.com">tier.phpjig.com</a>
        </div>
      </div>
    </div>
  </div>
{/block}