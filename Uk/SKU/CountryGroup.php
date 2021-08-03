<?php

namespace App\Acme\MatrixEngines\Uk\SKU;

use Closure;

class CountryGroup implements Pipe
{

    public function handle($content, Closure $next)
    {
        $sku = $this->getCountryGroup($content->data->get('country_of_residence'));
        if (!$sku) {
            $sku = $this->getCountryGroup($content->data->get('country_of_destination'));
        }

        $content->data->sku[] = $sku;
        return  $next($content);
    }

    private function getCountryGroup($country)
    {
        $country = strtoupper($country);

        $groups = [
            // notice UK, that has been added as an extra since GB is in the A group
            'A' => ['AT', 'FI', 'IT', 'NL', 'DE', 'MT', 'GR', 'GB', 'UK'],
            'B' => ['BE', 'CY', 'FR', 'PT', 'SI', 'ES', 'IE', 'SK', 'XP', 'QP', 'XE', 'IB'],
            'C' => ['HU', 'LU', 'RO', 'BG', 'DK', 'SE', 'PL', 'EE', 'LV', 'HR', 'LT', 'CZ', 'LI', 'NO', 'IS', 'XG']
        ];

        foreach ($groups as $group => $countries) {
            if (in_array($country, $countries)) {
                return $group;
            }
        }

        return null;
    }

}