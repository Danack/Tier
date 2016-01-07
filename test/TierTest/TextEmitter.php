<?php


namespace TierTest;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;

class TextEmitter implements EmitterInterface
{
    public function emit(ResponseInterface $response)
    {
        echo $response->getBody();
    }
}
