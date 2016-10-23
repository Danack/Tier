<?php

namespace Tier\Bridge;

use AurynConfig\InjectionParams;
use Jig\Jig;
use Room11\HTTP\Body\HtmlBody;
use Tier\Bridge\JigExecutable;
use Tier\Executable;

class TierJig
{
    private $jig;
    
    public function __construct(Jig $jig)
    {
        $this->jig = $jig;
    }

    public function createJigExecutable($templateName, InjectionParams $injectionParams = null)
    {
        if ($injectionParams === null) {
            $injectionParams = new InjectionParams();
        }
        $className = $this->jig->compile($templateName);
        $injectionParams->alias('Jig\JigBase', $className);

        return new Executable(['Tier\Bridge\TierJig', 'createHtmlBody'], $injectionParams);
    }

    public static function createHtmlBody(\Jig\JigBase $template)
    {
        $text = $template->render();

        return new HtmlBody($text);
    }
}
