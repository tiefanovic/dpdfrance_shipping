<?php
namespace DPDFrance\Shipping\Model\Config;

class Retour implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            '0'=>__('No returns'),
            '3'=>__('On Demand'),
            '4'=>__('Prepared')
        ];
    }
}
