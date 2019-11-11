<?php declare(strict_types=1);

namespace Circli\WebCore;

use Circli\WebCore\Exception\AccessDenied;
use Circli\WebCore\Exception\NotFoundInterface;

class ActionDispatcher extends \Polus\Adr\ActionDispatcher
{
    protected function handleInputException(\Throwable $e)
    {
        if ($e instanceof NotFoundInterface) {
            return $this->getResponseFactory()->createResponse(404);
        }
        if ($e instanceof AccessDenied) {
            return $this->getResponseFactory()->createResponse(403);
        }
        return parent::handleInputException($e);
    }
}