<?php

namespace App\Acme\MatrixEngines\Uk;

use App\Models\v3\Product;
use Illuminate\Pipeline\Pipeline;
use App\Acme\MatrixEngines\Uk\SKU\Age;
use App\Acme\MatrixEngines\Uk\SKU\SkuCover;
use App\Acme\MatrixEngines\Uk\SKU\ProductType;
use App\Acme\MatrixEngines\Uk\SKU\CountryGroup;
use App\Acme\MatrixEngines\AbstractMatrixEngine;


class CountryGroupMatrixEngine extends AbstractMatrixEngine
{

    public $pointsDefineSku = true;
    public $pipes;

    public function __construct(Product $product)
    {
        parent::__construct($product);
        $this->pipes = $this->loadPipes();
        $this->data->put('sku', '');

        //dd($this->data);        
    }

    /** Input validations rules
     * @return array
     */
    public function getValidationRules()
    {
        return [
            'country_of_residence'      => 'required',
            'country_of_destination'    => 'required',
            'date_of_birth'             => 'required|date_format:d/m/Y',
            'in_country_cover'          => 'required|in:0,1,true,false',
            'family_cover'              => 'required|in:0,1,true,false',
            'excess'                    => 'required|in:0,100,200,250'
        ];
    }

    /** Classes require for create sku
     *
     * @return array
     */
    public function loadPipes()
    {
        return [
            ProductType::class,
            CountryGroup::class,
            Age::class,
            SkuCover::class
        ];
    }

    /**
     * @return string
     */
    public function getX()
    {
        return 'Price';
    }

    /**
     * @return string
     */
    public function getY()
    {
        return $this->createSku();
    }

    /**
     * Create a SKU against user inputs
     *
     * @return string
     */
    private function createSku()
    {
        return app(Pipeline::class)
            ->send($this)
            ->through($this->pipes)
            ->then(function ($content) {
                return implode($content->data->sku, "-");
            });
    }
}