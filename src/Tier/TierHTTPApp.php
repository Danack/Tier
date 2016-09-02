<?php


namespace Tier;

use Auryn\Injector;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tier\Callback\MaxLoopCallback;

/**
 * Class TierHTTPApp
 *
 * An implementation of a TierApp that is targeted at processing PSR7
 * ServerRequestInterface requests.
 */
class TierHTTPApp extends TierApp
{
    /** @var OutputBufferCleaner(); */
    private $outputBufferCleaner;

    /**
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(
        Injector $injector,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct($injector, new MaxLoopCallback());
    }


    /**
     * @param Request $request
     */
    public function execute(Request $request)
    {
        try {
            $this->outputBufferCleaner = new OutputBufferCleaner();
            $this->injector->alias(
                'Psr\Http\Message\ServerRequestInterface',
                get_class($request)
            );
            $this->injector->share($request);
            $this->executeInternal();
            $this->outputBufferCleaner->checkOutputBufferCleared();
        }
        
//        catch (\Exception $e) {
//            while(ob_get_level() > 0) {
//                ob_end_flush();
//            }
//            echo "Excepion message is : [" . $e->getMessage() . "]";
//            
//            echo "omg";
//            throw $e;
//            //exit(0);
//        }
        finally {
            $this->outputBufferCleaner->clearOutputBuffer();
            //echo "wtf";
            //exit(0);
        }
    }

}
