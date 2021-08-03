<?php

namespace App\Acme\MatrixEngines;

use App\Models\v3\Product;
use Illuminate\Support\Collection;
use App\Exceptions\VariantNotFoundException;

abstract class AbstractMatrixEngine
{
    public $product;
    public $matrix;

    /** @var Collection */
    public $data;

    public $pointsDefineSku = false;

    /**
     * AbstractMatrix constructor.
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->matrix = $product->matrixType;
        $this->setData();
    }

    public function setData($data = [])
    {
        $this->data = collect($data);
        return $this;
    }

    /**
     * @return array
     */
    abstract public function getValidationRules();

    /**
     * @return string
     */
    abstract public function getX();

    /**
     * @return string
     */
    abstract public function getY();


    /**
     * @param Collection $metaData
     * @return array
     */
    public function getVariant(Collection $metaData)
    {

        $this->setData($metaData);

        $variants = collect($this->product->variants);

        if ($this->data->has('variant_key')) {
            $variant = $variants->where('key', $this->data->get('variant_key'))->first();
        } else {

            if ($this->pointsDefineSku) {

                $sku = "{$this->product->pid}-X_{$this->getX()}-Y_{$this->getY()}";

                $variant = $variants->filter(function ($variant) use ($sku) {

                    if (method_exists($this, 'skipVariant') && $this->skipVariant($variant)) {
                        return false;
                    }

                    return strpos($variant['sku'], $sku) !== false;

                })->first();

            } else {

                $variant = $variants->where('xid', $this->getX())
                    ->where('yid', $this->getY())
                    ->first();

            }
        }

        if (!$variant) {
            $exception = new VariantNotFoundException();
            $exception->setMatrixEngine($this);

            if (isset($sku)) {
                $exception->setSku($sku);
            }

            throw $exception;
        }

        return $variant;
    }
}