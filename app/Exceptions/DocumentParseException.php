<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class DocumentParseException extends Exception
{
    public function __construct(
        string $message = '',
        private ?string $hint = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function render($request)
    {
        $payload = ['message' => __($this->getMessage())];

        if ($this->hint) {
            $payload['hint'] = __($this->hint);
        }

        return response()->json($payload, 500);
    }
}

