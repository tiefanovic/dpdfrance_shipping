<?php
namespace DPDFrance\Shipping\Model\Config;

class Advalorem implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            '0'=>__('Integrated insurance (23€ / transported kg - LOTI cdts.)'),
            '1'=>__('Ad Valorem insurance service')
        ];
    }
}
