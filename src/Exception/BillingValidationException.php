<?php

namespace App\Exception;

class BillingValidationException extends \Exception
{
    public function __construct(
        string $message,
        private array $violations = [],
        int $code = 422,
    ) {
        parent::__construct($message, $code);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
