<?php


namespace Tier;

class TierHTTPApp extends TierApp
{
    const STAGE_INITIAL = 10;
    const STAGE_BEFORE_BODY = 20;
    const STAGE_GENERATE_BODY = 30;
    const STAGE_BEFORE_SEND = 40;
    const STAGE_SEND = 50;
    const STAGE_AFTER_SEND = 60;
    
    /**
     * @param $callable
     */
    public function addPreCallable($callable)
    {
        $this->tiersByStage->addTier(TierHTTPApp::STAGE_BEFORE_BODY, $callable);
    }

    // This can't be type-hinted as callable as we allow instance methods
    // on uncreated classes.
    public function addResponseCallable($callable)
    {
        $this->tiersByStage->addTier(TierHTTPApp::STAGE_GENERATE_BODY, $callable);
    }

    public function addSendCallable($callable)
    {
        $this->tiersByStage->addTier(TierHTTPApp::STAGE_SEND, $callable);
    }

    // This can't be type-hinted as callable as we allow instance methods
    // on uncreated classes.
    public function addPostCallable($callable)
    {
        $this->tiersByStage->addTier(TierHTTPApp::STAGE_AFTER_SEND, $callable);
    }

    public function addBeforeSendCallable($callable)
    {
        $this->tiersByStage->addTier(TierHTTPApp::STAGE_BEFORE_SEND, $callable);
    }
}
