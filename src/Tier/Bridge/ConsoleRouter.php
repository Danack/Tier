<?php

namespace Tier\Bridge;

use Danack\Console\Application as ConsoleApplication;
use Danack\Console\Output\BufferedOutput;
use Danack\Console\Formatter\OutputFormatterStyle;
use Danack\Console\Helper\QuestionHelper;
use Tier\Executable;
use AurynConfig\InjectionParams;

class ConsoleRouter
{
    public static function routeCommand(ConsoleApplication $console)
    {
        // TODO - currently in PHP it is impossible to make a constructor of a child class
        // have a constructor that has more restricted visibility than then parent class.
        // In an ideal world, ConsoleApplication would be a child class that does not have a
        // default constructor, as that would prevent 'accidental' insertion of an unitialized
        // object.
        // However that is not currently possible, and would require an RFC to change.

        //Figure out what Command was requested.
        try {
            $parsedCommand = $console->parseCommandLine();
        }
        catch (\Exception $e) {
            //@TODO change to just catch parseException when that's implemented
            $output = new BufferedOutput();
            $console->renderException($e, $output);
            echo $output->fetch();
            exit(-1);
        }
    
        $output = $parsedCommand->getOutput();
        $formatter = $output->getFormatter();
        $formatter->setStyle('question', new OutputFormatterStyle('blue'));
        $formatter->setStyle('info', new OutputFormatterStyle('blue'));
        $questionHelper = new QuestionHelper();
        $questionHelper->setHelperSet($console->getHelperSet());
        $injectionParams = InjectionParams::fromParams($parsedCommand->getParams());
        
        $executable = new Executable($parsedCommand->getCallable(), $injectionParams);
        $executable->setAllowedToReturnNull(true);
    
        return $executable;
    }
}
