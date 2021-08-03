<?php

namespace App\Acme\MatrixEngines\Uk;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SkuService
{
    protected $data;

    public function __construct()
    {
        $this->data = [];
    }

    // Country Of residence
    // Age Band 21-85
    // In-Country
    // Family Cover
    // Excess

    public function dailyData()
    {
        $daily = range(1, 10);
        array_walk($daily, function(&$value, $key) { $value = 'ST'.$value; } );
        $this->data = array(
            0 =>  $daily,
            1 => array('A', 'B', 'C'),
            2 => range(21, 85),
            3 => array(0),      // In-Country
            4 => array(0),      // Family Cover
            5 => array(0, 250)    // Excess
        );
    }

    public function vanHireData()
    {
        $daily = range(1, 10);
        array_walk($daily, function(&$value, $key) { $value = 'ST'.$value; } );
        $this->data = array(
            0 =>  $daily,
            1 => array('A', 'B', 'C'),
            2 => range(21, 85),
            3 => array(1),      // In-Country
            4 => array(0),      // Family Cover
            5 => array(100, 200)// Excess
        );
    }

    public function yearlyData()
    {
        $this->data = array(
            0 =>  ['AMT'],
            1 => array('A', 'B', 'C'),
            2 => range(21, 85),
            3 => array(0, 1),      // In-Country
            4 => array(0, 1),      // Family Cover
            5 => array(0, 250)       // Excess
        );
    }

    // daily //yearly // vanHire
    public function createMatrix($method = 'vanHire')
    {
        $this->{$method.'Data'}();
        $filename = "$method.csv";
        $list = $this->permutations($this->data);
        $headers = array(
            'Content-Type' => 'text/csv',
        );
        $FH = fopen($filename, 'w');
        foreach ($list as $row) {
            fputcsv($FH, [implode($row, '-')]);
        }
        fclose($FH);
        return response()->download($filename);

    }

    // daily //yearly // vanHire
    public function export($method = 'vanHire')
    {
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$method.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $this->{$method.'Data'}();
        $filename = "ukclass_".$method."_pricing.csv";

        $list = $this->permutations($this->data);
        $columns = array('SKU', 'Price', 'SKU', 'Underwriter Cost', 'Commission Cost', 'Underwriter Code', 'Underwriter Name');

        $file = fopen($filename, 'w');
        fputcsv($file, $columns);

        foreach($list as $data) {
            $uw_cost = $this->{'cost'.$method}($data, 24);
            $commission_cost = $this->{'cost'.$method}($data, 13);

            $sku = implode($data, '-');
            fputcsv($file, array($sku, 'Price', $sku, $uw_cost, $commission_cost, "ZUR", "Zurich EU"));
        }
        fclose($file);
        return response()->download($filename);
    }

    public function uwCostCountry($band)
    {
        $uw_cost_country = ['A' => 0, 'B' => 3, 'C' => 6];
        return data_get($uw_cost_country, $band);
    }

    public function uwCostAge($age)
    {
        $uw_cost_age = [
            array(21 => 12, 22 => 11, 23 => 10, 24 => 10),
            array_fill_keys(range(25,28),9),
            array_fill_keys(range(29,31),8),
            array_fill_keys(range(32,35),4),
            array_fill_keys(range(36,39),2),
            array_fill_keys(range(40,49),0),
            array_fill_keys(range(50,85),3),
        ];

        foreach ($uw_cost_age as $age_range){
            if(array_key_exists($age, $age_range)){
                return $age_range[$age];
            }
        }
        return null;
    }

    public function uwCostInCountry($cover)
    {
        return $cover ? 12 : 0;
    }

    public function uwCostLOCC($cover)
    {
        return $cover ? 12 : 0;
    }

    public function uwCostFamily($cover)
    {
        return $cover ? 12 : 0;
    }

    public function daily($day)
    {
        $days = array('ST1' => 1, 'ST2' => 2, 'ST3' => 3, 'ST4' => 4, 'ST5' => 5, 'ST6' => 6, 'ST7' => 7, 'ST8' => 8, 'ST9' => 9, 'ST10' => 10);
        return data_get($days, $days);
    }

    public  function costyearly($sku, $base)
    {
        $country = $this->uwCostCountry($sku[1]);
        $age     = $this->uwCostAge($sku[2]);
        $inCountryCover = $this->uwCostInCountry($sku[3]);
        $locc = $this->uwCostLOCC($sku[4]);
        $family = $this->uwCostFamily($sku[5]);

        $total = $country + $age + $inCountryCover + $locc + $family;
        $temp = (float)((($total / 100) * $base) + $base);
        $cost = number_format($temp, 2, '.', '');
        return $cost;
    }

    public  function costdaily($sku, $base)
    {
        $daily = $this->daily($sku[0]);
        $country = $this->uwCostCountry($sku[1]);
        $age     = $this->uwCostAge($sku[2]);
        $inCountryCover = $this->uwCostInCountry($sku[3]);
        $locc = $this->uwCostLOCC($sku[4]);
        $family = $this->uwCostFamily($sku[5]);

        $total = $daily + $country + $age + $inCountryCover + $locc + $family;
        $temp = (($total / 100) * $base) + $base;
        $cost = number_format($temp, 2, '.', '');
        return $cost;
    }


    public  function costvanHire($sku, $base)
    {
        $daily = $this->daily($sku[0]);
        $country = $this->uwCostCountry($sku[1]);
        $age     = $this->uwCostAge($sku[2]);
        $inCountryCover = $this->uwCostInCountry($sku[3]);
        $locc = $this->uwCostLOCC($sku[4]);
        $family = $this->uwCostFamily($sku[5]);

        $total = $daily + $country + $age + $inCountryCover + $locc + $family;
        $temp = (($total / 100) * $base) + $base;
        $cost = number_format($temp, 2, '.', '');
        return $cost;
    }

    function permutations(array $array, $inb=false)
    {
        switch (count($array)) {
            case 1:
                // Return the array as-is; returning the first item
                // of the array was confusing and unnecessary
                return $array[0];
                break;
            case 0:
                throw new InvalidArgumentException('Requires at least one array');
                break;
        }

        // We 'll need these, as array_shift destroys them
        $keys = array_keys($array);

        $a = array_shift($array);
        $k = array_shift($keys); // Get the key that $a had
        $b = $this->permutations($array, 'recursing');

        $return = array();
        foreach ($a as $v) {
            foreach ($b as $v2) {
                // array($k => $v) re-associates $v (each item in $a)
                // with the key that $a originally had
                // array_combine re-associates each item in $v2 with
                // the corresponding key it had in the original array
                // Also, using operator+ instead of array_merge
                // allows us to not lose the keys once more
                if($inb == 'recursing'){
                    $return[] = array_merge(array($v), (array) $v2);
                }
                else
                    $return[] = array($k => $v) + array_combine($keys, $v2);
            }
        }
        return $return;
    }
}