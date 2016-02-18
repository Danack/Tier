<?php

namespace Tier\Bridge;

use Jig\Jig;
use Tier\Bridge\JigExecutable;
use Tier\InjectionParams;
use Tier\Executable;
use Room11\HTTP\Body\HtmlBody;

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
            $injectionParams = InjectionParams::fromParams([]);
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
