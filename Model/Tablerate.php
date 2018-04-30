<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Model;

class Tablerate extends \Magento\OfflineShipping\Model\Carrier\Tablerate
{
    protected function _construct()
    {
        $this->_init('dpd_shipping_tablerate', 'entity_id');
    }
}
