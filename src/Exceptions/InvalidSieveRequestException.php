<?php

namespace Serri\Alchemist\Exceptions;

class InvalidSieveRequestException extends AlchemistException
{
    /**
     * @param  array<int, string>  $offenders
     */
    public static function disallowed(array $offenders): self
    {
        $list = implode(', ', $offenders);

        return new self("The request asks for fields the formula does not permit: $list.");
    }
}
