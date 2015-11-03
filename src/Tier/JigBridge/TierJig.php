<?php

namespace Tier\JigBridge;

use Jig\Jig;
use Tier\InjectionParams;
use Tier\Executable;
use Room11\HTTP\Body\HtmlBody;

function createHtmlBody(\Jig\JigBase $template)
{
    $text = $template->render();

    return new HtmlBody($text);
}

class TierJig
{
    private $jig;
    
    public function __construct(Jig $jig)
    {
        $this->jig = $jig;
    }
    
    public function createTemplateTier($templateName, InjectionParams $injectionParams = null)
    {
        if ($injectionParams == null) {
            $injectionParams = InjectionParams::fromParams([]);
        }
        $this->jig->checkTemplateCompiled($templateName);
        $className = $this->jig->getFQCNFromTemplateName($templateName);
        $injectionParams->alias('Jig\JigBase', $className);

        return new Executable('Tier\JigBridge\createHtmlBody', $injectionParams);
    }
}
