<?php

namespace Tier\JigBridge;

use Jig\Jig;
use Tier\InjectionParams;
use Tier\Tier;
use Tier\ResponseBody\HtmlBody;

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

        $className = $this->jig->getTemplateCompiledClassname($templateName);
        $this->jig->checkTemplateCompiled($templateName);
        $injectionParams->alias('Jig\JigBase', $className);

        return new Tier('TierBridge\createHtmlBody', $injectionParams);
    }
}

