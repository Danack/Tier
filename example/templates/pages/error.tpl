{extends file="component/blankPage"}

{block name='content'}
    {inject name='errorInfo' type='Tier\Data\ErrorInfo'}

    <h3>{$errorInfo->title}</h3>
    <p>
        Oops something went wrong getting info from Github: {$errorInfo->description}
    </p>
{/block}