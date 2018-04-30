<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Config\Source\Settings;

use Magento\Framework\Option\ArrayInterface;

class Environment implements ArrayInterface
{
    /**
     * Return mode option array
     * @return array
     */
    public function toOptionArray()
    {
        // @codingStandardsIgnoreStart
        $options = [
			['value' => '0', 'label' => __('Please select an environment')],
            ['value' => '1', 'label' => __('Live')],
            ['value' => '2', 'label' => __('Demo')],
        ];
        // @codingStandardsIgnoreEnd
        return $options;
    }
}
