<?php


namespace Tier\Bridge;

use Auryn\Injector;
use Jig\Jig;
use Jig\JigBase;
use Room11\HTTP\Body\HtmlBody;
use Tier\Executable;
use AurynConfig\InjectionParams;

class JigExecutable
{
    public static function createWithSharedObjects(
        $templateName,
        array $sharedObjects = []
    ) {
        $injectionParams = new InjectionParams($sharedObjects);

        return self::create($templateName, $injectionParams);
    }

    /**
     * @param $templateName
     * @param InjectionParams $injectionParams
     * @return Executable
     */
    public static function create(
        $templateName,
        InjectionParams $injectionParams = null
    ) {
        if ($injectionParams === null) {
            $injectionParams = new InjectionParams();
        }

        // This uses double-dispatch so that the first Executable can have it's
        // dependency injected, and then the second Exectuable that actually renders the
        // template has its dependencies injected separately.
        $fn = function (Jig $jigRender) use ($templateName, $injectionParams) {
            $className = $jigRender->compile($templateName);
            $injectionParams->alias('Jig\JigBase', $className);
            $fn = function (Injector $injector) use ($className) {
                return $injector->make($className);
            };
            $injectionParams->delegate('Jig\JigBase', $fn);

            return new Executable(['Tier\Bridge\JigExecutable', 'createHtmlBody'], $injectionParams);
        };

        return new Executable($fn);
    }

    /**
     * @param JigBase $template
     * @return HtmlBody
     * @throws \Exception
     * @throws \Jig\JigException
     */
    public static function createHtmlBody(JigBase $template)
    {
        $text = $template->render();

        return new HtmlBody($text);
    }
}
