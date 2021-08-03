<?php

namespace App\Acme\MatrixEngines\Uk\SKU;

use Closure;

class SkuCover implements Pipe
{
    public function handle($content, Closure $next)
    {
        $content->data->sku[] = $this->countryCover($content->data->get('in_country_cover'));
        $content->data->sku[] = $this->familyCover($content->data->get('family_cover'));
        $content->data->sku[] = $this->excess($content->data->get('excess'));

        return  $next($content);
    }

    private function countryCover($in_country)
    {
        return ($in_country == 1) ? '1' : '0';
    }

    private function familyCover($cover)
    {
        return ($cover == 1) ? '1' : '0';
    }

    private function excess($cover)
    {
        return $cover;
    }
}