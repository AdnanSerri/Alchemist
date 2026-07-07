<?php

use Serri\Alchemist\Services\Alchemist;

if (! function_exists('alchemist')) {
    function alchemist(): Alchemist
    {
        return app(Alchemist::class);
    }
}
