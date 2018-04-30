<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Config\Source\Settings;

use Magento\Framework\Option\ArrayInterface;

class RateType implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            array(
                'value' => 'flat',
                'label' => __('Flat'),
			),
            array(
                'value' => 'table',
                'label' => __('Table'),
            ),
        );
        return $options;
    }
}
