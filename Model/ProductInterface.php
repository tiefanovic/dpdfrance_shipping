<?php
/**
 * Created by PhpStorm.
 * User: ahmed
 * Date: 14/02/18
 * Time: 12:03 ص
 */

namespace DPDFrance\Shipping\Model;


interface ProductInterface
{
    public function getOption($option);
    public function getAttribute($attribute);
    public function getName();
    public function getSku();
    public function getQuantity();
    public function getStockData($key);
}