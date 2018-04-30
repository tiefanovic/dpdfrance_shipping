<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Config\Source\Settings;

use Magento\Framework\Option\ArrayInterface;

class PrintFormat implements ArrayInterface
{
	/**
	 * Return mode option array
	 * @return array
	 */
	public function toOptionArray()
	{
		// @codingStandardsIgnoreStart
		$options = [
			['value' => 'A4', 'label' => __('A4')],
			['value' => 'A6', 'label' => __('A6')],
		];
		// @codingStandardsIgnoreEnd
		return $options;
	}
}
