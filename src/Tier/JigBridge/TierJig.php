<?php

namespace Tier\JigBridge;

use Jig\Jig;
use Jig\JigBase;
use Tier\InjectionParams;
use Tier\Tier;
use Room11\HTTP\Body\HtmlBody;

/**
 * Class TierJig This is a helper class to make it easier to use Jig templates 
 * in a project based on Tier. 
 * @package Tier\JigBridge
 */
class TierJig
{
    private $jig;
    
    public function __construct(Jig $jig)
    {
        $this->jig = $jig;
    }

    /**
     * Create a new Tier that will render a template
     * @param $templateName string The template to render
     * @param InjectionParams $injectionParams The injection params to be passed to the new Tier
     * @return Tier
     */
    public function createTemplateTier($templateName, InjectionParams $injectionParams = null)
    {
        if ($injectionParams == null) {
            $injectionParams = InjectionParams::fromParams([]);
        }

        $className = $this->jig->getTemplateCompiledClassname($templateName);
        $this->jig->checkTemplateCompiled($templateName);
        $injectionParams->alias('Jig\JigBase', $className);

        $createHtmlBody = function (\Jig\JigBase $template) {
            $text = $template->render();
        
            return new HtmlBody($text);
        };

        return new Tier($createHtmlBody, $injectionParams);
    }
}

