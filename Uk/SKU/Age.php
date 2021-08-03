<?php

namespace App\Acme\MatrixEngines\Uk\SKU;

use Closure;
use Carbon\Carbon;

class Age implements Pipe
{

    public function handle($content, Closure $next)
    {
        $age = $this->getAge($content->data->get('date_of_birth'));

        $content->data->sku[] = $age;
        return  $next($content);
    }

    /** Get age from date of birth
     * @param $dob
     * @return int
     */
    private function getAge($dob)
    {
        $dateOfBirth = Carbon::createFromFormat('d/m/Y', $dob);
        return $dateOfBirth->age;
    }

}