<?php

namespace App\Acme\MatrixEngines\Uk\SKU;

use Closure;

interface Pipe
{
    public function handle($content, Closure $next);
}