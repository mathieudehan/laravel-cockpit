<?php

namespace Mathieu\Cockpit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mathieu\Cockpit\CockpitServiceProvider
 */
class Cockpit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cockpit';
    }
}
