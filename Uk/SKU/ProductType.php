<?php

namespace App\Acme\MatrixEngines\Uk\SKU;

use Closure;
use Carbon\Carbon;
use App\Models\v3\Product;

class ProductType implements Pipe
{

    public function handle($content, Closure $next)
    {
        if($content->product->durationType === Product::DAILY){

            $startDate = $this->createDate($content->data->get('start_date'));
            $endDate   = $this->createDate($content->data->get('end_date'));
            $dailyLimit = $startDate->diffInDays($endDate);
            $dailyLimit++; // the day itself will be included
            if($dailyLimit >= 10){
                $limit = 10;
            }else if($dailyLimit <= 0){
                $limit = 1;
            }else{
                $limit = $dailyLimit;
            }
            $content->data->sku[] = 'ST'.$limit;
            return  $next($content);
        }

        $content->data->sku[] = 'AMT';
        return  $next($content);
    }

    public function createDate($date, $format = 'd/m/Y H:i:s')
    {
        return Carbon::createFromFormat($format, $date);
    }
}