<?php

namespace YLYPlatform\Kernel\Exceptions;


use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Class HttpException
 * @package YLYPlatform\Kernel\Exceptions
 */
class HttpException extends Exception
{
    public $response;
    public $formattedResponse;

    public function __construct($message = "", ResponseInterface $response = null, $formattedResponse = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->response = $response;
        $this->formattedResponse = $formattedResponse;

        if ($response) {
            $response->getBody()->rewind();
        }
    }


}