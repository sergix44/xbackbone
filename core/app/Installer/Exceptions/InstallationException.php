<?php

namespace XBB\Installer\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Raised when a finalize phase fails. The optional step lets the wizard jump
 * the user back to the offending step with their input intact.
 */
class InstallationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $step = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function atStep(int $step, string $message, ?Throwable $previous = null): self
    {
        return new self($message, $step, $previous);
    }
}
