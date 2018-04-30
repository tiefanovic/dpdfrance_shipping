<?php
namespace DPDFrance\Shipping\Block\Adminhtml;

class OrderManagement extends \Magento\Backend\Block\Widget\Grid\Container
{

	protected function _construct()
	{
		$this->_controller = 'adminhtml_orders';
		$this->_blockGroup = 'DPDFrance_Shipping';
		$this->_headerText = __('Orders Management');
		parent::_construct();
	}
}