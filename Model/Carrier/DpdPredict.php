<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Model\Carrier;

use DPDFrance\Shipping\Model\Owebia;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Magento\Customer\Model\Group;
use Magento\Directory\Model\Country;

class DpdPredict extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
		\Magento\Shipping\Model\Carrier\CarrierInterface
{

	protected $_code = 'dpdfrpredict';
	protected $_trackFactory;
	protected $_trackingPopupFactory;
	protected $_trackErrorFactory;
	protected $_defaultConditionName = 'package_weight';
	protected $checkOutSession;
	protected $taxCalculation;
	protected $_result = null;
	protected $_tablerateFactory;
	protected $trackStatusFactory;
	protected $backendQoute;
	protected $storeManager;
	protected $_owebiaCore;
	protected $_messages;
	protected $currencyHelper;
	protected $methodFactory;
	protected $customerSession;
	protected $customerGroup;
	protected $countryModel;
	protected $_expenseConfig = null;
	
	public function __construct(
			\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
			\Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
			\Psr\Log\LoggerInterface $logger,
			\Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
			\Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
			\Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
			\DPDFrance\Shipping\Helper\TrackingPopupFactory $trackingPopupFactory,
			\Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
			\Magento\Backend\Model\Session\Quote $backendQoute,
			\Magento\Framework\Pricing\Helper\Data $currencyHelper,
			StoreManagerInterface $storeManager,
			\Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methodFactory,
			Session $checkOutSession,
			Calculation $calculation,
			\DPDFrance\Shipping\Model\ResourceModel\TablerateFactory $tablerateFactory,
			\Magento\Customer\Model\Session $customerSession,
			Group $customerGroup,
			Country $countryModel,
			array $data = []

	) {
		$this->_rateResultFactory = $rateResultFactory->create();
		$this->_trackFactory = $trackFactory;
		$this->_trackingPopupFactory = $trackingPopupFactory;
		$this->_trackErrorFactory = $trackErrorFactory;
		$this->_tablerateFactory = $tablerateFactory;
		$this->taxCalculation = $calculation;
		parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
		$this->checkOutSession = $checkOutSession;
		$this->trackStatusFactory = $trackStatusFactory;
		$this->backendQoute = $backendQoute;
		$this->storeManager = $storeManager;
		$this->currencyHelper = $currencyHelper;
		$this->methodFactory = $methodFactory;
		$this->customerSession = $customerSession;
		$this->customerGroup = $customerGroup;
		$this->countryModel = $countryModel;
	}
	public function isTrackingAvailable()
	{
		return true;
	}
	public function getAllowedMethods()
	{
		return ['dpdfrpredict' => $this->_scopeConfig->getValue('carriers/dpdfrpredict/name')];
	}
	public function getResult()
	{
		if (!$this->_result) {
			$this->_result = $this->_trackFactory->create();
		}
		return $this->_result;
	}
	public function getTracking($trackings)
	{

		if (!is_array($trackings)) {
			$trackings = [$trackings];
		}
		foreach ($trackings as $tracking)
			$this->getTrackingInfo($tracking);

		return $this->_result;
	}
	public function getTrackingInfo($trackingNumber)
	{
		$trackingUrlByRef = 'http://www.dpd.fr/tracex_'.$trackingNumber;
		$trackingUrlByNb = 'http://www.dpd.fr/traces_'.$trackingNumber;
		$cargo = $this->_scopeConfig->getValue('carriers/dpdfrpredict/cargo');
		$longueurcargo = strlen($cargo);
		$cargoutilise = substr($trackingNumber, -(int) $longueurcargo);
		$result = $this->getResult();
		if($cargoutilise == $cargo){
			$tracking = $this->trackStatusFactory->create();
			$tracking->setCarrier('dpdfrpredict');
			$tracking->setCarrierTitle('DPD Predict');
			$tracking->setTracking($trackingNumber);
			$tracking->addData(array('status'=>'<a href="'.$trackingUrlByRef.'" target="_blank">Cliquez ici pour suivre votre colis </a>'));
			$result->append($tracking);

		}else{
			$tracking = $this->trackStatusFactory->create();
			$tracking->setCarrier('dpdfrpredict');
			$tracking->setCarrierTitle('DPD Predict');
			$tracking->setTracking($trackingNumber);
			$tracking->addData(array('status'=>'<a href="'.$trackingUrlByNb.'" target="_blank">Cliquez ici pour suivre votre colis </a>'));
			$result->append($tracking);
		}
	}
	public function getRateByTableRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
	{
		return $this->_tablerateFactory->create()->getRate($request);
	}
	protected function _getCartTaxAmount()
	{
		$quote = $this->checkOutSession->getQuote();
		$items_in_cart = $quote->getAllVisibleItems();
		$tax_amount = 0;
		if(count($items_in_cart) > 0){
			foreach ($items_in_cart as $item)
			{
				$rates = $this->taxCalculation->getAppliedRates($this->taxCalculation->getRateRequest());
				$vat_rate = isset($rates[$item->getProduct()->getTaxClassId()]) ? $rates[$item->getProduct()->getTaxClassId()] : 0;
				if($vat_rate > 0 )
					$tax_amount += $item->getRowTotal() * $vat_rate/100;
				else
					$tax_amount +=  $item->getTaxAmount();
			}
		}
		return $tax_amount;
	}
	public function collectRates(RateRequest $request)
	{
		/* die(var_dump($this->getConfigFlag('active')));*/
         if (!$this->getConfigFlag('active')) {
             return false;
         }

		/** @var \Magento\Shipping\Model\Rate\Result $result */

		$result = $this->_rateResultFactory;

		/** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
		$method = $this->methodFactory->create();

		$method->setCarrier('dpdfrpredict');
		$method->setCarrierTitle($this->_scopeConfig->getValue('carriers/dpdfrpredict/title'));

		$method->setMethod('dpdfrpredict');
		$method->setMethodTitle($this->_scopeConfig->getValue('carriers/dpdfrpredict/name'));

		if($this->_scopeConfig->getValue('carriers/dpdfrpredict/rate_type') == 'table')
		{
			// Possible bug in Magento, new sessions post no data when fetching the shipping methods, only country_id: US
			// This prevents the tablerates from showing a 0,00 shipping price
			if(!$request->getDestPostcode() && $request->getDestCountryId() == 'US') {
				return false;
			}
			// Free shipping by qty
			$freeQty = 0;
			$freePackageValue = 0;
			if ($request->getAllItems()) {
				foreach ($request->getAllItems() as $item) {
					if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
						continue;
					}
					if ($item->getHasChildren() && $item->isShipSeparately()) {
						foreach ($item->getChildren() as $child) {
							if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
								$freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
								$freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
							}
						}
					} elseif ($item->getFreeShipping()) {
						$freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
						$freeQty += $item->getQty() - $freeShipping;
						$freePackageValue += $item->getBaseRowTotal();
					}
				}
				$oldValue = $request->getPackageValue();
				$request->setPackageValue($oldValue - $freePackageValue);
			}

			$conditionName = $this->_scopeConfig->getValue('dpdshipping/tablerate/condition_name', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
			$request->setConditionName($conditionName ? $conditionName : $this->_defaultConditionName);

			// Package weight and qty free shipping
			$oldWeight = $request->getPackageWeight();
			$oldQty = $request->getPackageQty();

			$request->setPackageWeight($request->getFreeMethodWeight());
			$request->setPackageQty($oldQty - $freeQty);

			$rate = $this->getRateByTableRate($request);

			$method->setPrice($rate['price']);
			$method->setCost($rate['cost']);
			$result->append($method);
		}else{
			$this->getRate($request);

		}
		return $result;
	}

	protected function getRate(RateRequest $request)
	{
		try {
			$process = array(
					'result' => $this->_rateResultFactory,
					'cart.items' => array(),
					'products' => array(),
					'data' => array(
							'cart.price_excluding_tax' => $request->getPackageValueWithDiscount(),
							'cart.price_including_tax' => $request->getPackageValueWithDiscount(),
							'cart.weight' => $request->getPackageWeight(),
							'cart.weight.unit' => null,
							'cart.quantity' => $request->getPackageQty(),
							'cart.coupon' => ($this->checkOutSession->getQuote()->hasCouponCode() ? $this->checkOutSession->getQuote()->getCouponCode() : $this->backendQoute->getQuote()->getCouponCode()),
							'destination.country.code' => $request->getDestCountryId(),
							'destination.country.name' => null,
							'destination.region.code' => $request->getDestRegionCode(),
							'destination.postal.code' => $request->getDestPostcode(),
							'origin.country.code' => $request->getOrigCountryId(),
							'origin.country.name' => null,
							'origin.region.code' => $request->getOrigRegionId(),
							'origin.postal.code' => $request->getOrigPostcode(),
							'customer.group.id' => null,
							'customer.group.code' => null,
							'free_shipping' => $request->getFreeShipping(),
							'store.id' => $request->getStoreId(),
							'store.code' => null,
							'store.name' => null,
							'store.address' => null,
							'store.phone' => null,
							'date.timestamp' => null,
							'date.year' => null,
							'date.month' => null,
							'date.day' => null,
							'date.hour' => null,
							'date.minute' => null,
							'date.second' => null,
					),
					'stop_to_first_match' => TRUE,
					'config' => null,
			);
			// We don't need process certain products. If necessary, enable this block.
			$items = $request->getAllItems();
			for ($i=0, $n=count($items); $i<$n; $i++) {
				$item = $items[$i];
				if ($item->getProduct() instanceof Product) $process['cart.items'][$item->getId()] = $item;
				$bundleProcessChildren = true;
				$type = $item->getProduct()->getTypeId();
				$parentItemId = $item->getData('parent_item_id');
				$parentItem = isset($items[$parentItemId]) ? $items[$parentItemId] : null;
				$parentType = isset($parentItem) ? $parentItem->getProduct()->getTypeId() : null;
				if ($type != 'configurable') {
					if ($type == 'bundle' && $bundleProcessChildren) {
						$this->_data['qty'] -= $item->getQty();
						continue;
					}
					if ($parentType == 'bundle') {
						if (!$bundleProcessChildren) continue;
						else $this->_data['qty'] += $item->getQty();
					}
				}
			}

			$this->_process($process);
		}
		catch (\Exception $e){
			$this->_logger->debug($e);
		}
	}
	protected function _process(&$process) {
		$store = $this->storeManager->getStore($process['data']['store.id']);
		$timestamp = time();
		$customer_group_id = $this->customerSession->getCustomerGroupId();

		// Pour les commandes depuis Adminhtml
		if ($customer_group_id==0) {
			$customer_group_id2 = $this->backendQoute->getQuote()->getCustomerGroupId();
			if (isset($customer_group_id2)) $customer_group_id = $customer_group_id2;
		}

		$customer_group_code = $this->customerGroup->load($customer_group_id)->getData('customer_group_code');
		$process['data'] = array_merge($process['data'],array(
				'customer.group.id' => $customer_group_id,
				'customer.group.code' => $customer_group_code,
				'destination.country.name' => $this->_getCountryName($process['data']['destination.country.code']),
				'destination.postal.code' => $this->checkOutSession->getQuote()->getShippingAddress()->getPostcode(),
				'origin.country.name' => $this->_getCountryName($process['data']['origin.country.code']),
				'cart.weight.unit' => 'kg',
				'store.code' => $store->getCode(),
				'store.name' => $this->_scopeConfig->getValue('general/store_information/name',ScopeInterface::SCOPE_STORE, $store),
				'store.address' => $this->_scopeConfig->getValue('general/store_information/address',ScopeInterface::SCOPE_STORE, $store),
				'store.phone' => $this->_scopeConfig->getValue('general/store_information/phone',ScopeInterface::SCOPE_STORE, $store),
				'date.timestamp' => $timestamp,
				'date.year' => (int)date('Y',$timestamp),
				'date.month' => (int)date('m',$timestamp),
				'date.day' => (int)date('d',$timestamp),
				'date.hour' => (int)date('H',$timestamp),
				'date.minute' => (int)date('i',$timestamp),
				'date.second' => (int)date('s',$timestamp),
		));
		// We don't need process certain products. If necessary, enable this block.
		foreach ($process['cart.items'] as $id => $item) {
			if ($item->getProduct()->getTypeId()!='configurable') {
				$parent_item_id = $item->getParentItemId();
				$process['products'][] = new \DPDFrance\Shipping\Model\Product($item, isset($process['cart.items'][$parent_item_id]) ? $process['cart.items'][$parent_item_id] : null);
			}
		}

		if (!$process['data']['free_shipping']) {
			foreach ($process['cart.items'] as $item) {
				if ($item->getProduct() instanceof Product) {
					if ($item->getFreeShipping()) $process['data']['free_shipping'] = true;
					else {
						$process['data']['free_shipping'] = false;
						break;
					}
				}
			}
		}

		$process['data']['cart.price_including_tax'] = $this->_getCartTaxAmount() + $process['data']['cart.price_excluding_tax'];

		$value_found = false;
		foreach ($this->_getConfig() as $row) {
			$result = $this->_owebiaCore->processRow($process, $row);
			$this->_addMessages($this->_owebiaCore->getMessages());
			if ($result->success) {
				if ($process['stop_to_first_match'] && $value_found) {
					break;
				}
				$value_found = true;
				$this->_appendMethod($process, $row, $result->result);
			}
		}

		if (!$value_found && $this->_scopeConfig->getValue('carriers/dpdfrpredict/showerror'))
			$this->_setError($process, $this->_scopeConfig->getValue('carriers/dpdfrpredict/specificerrmsg'));
	}
	protected function _getConfig() {
		if (!isset($this->_expenseConfig)) {
			$this->_owebiaCore = new Owebia($this->_scopeConfig->getValue('carriers/dpdfrpredict/expense'));
			$this->_expenseConfig = $this->_owebiaCore->getConfig();
			$this->_addMessages($this->_owebiaCore->getMessages());
		}
		return $this->_expenseConfig;
	}
	protected function _addMessages($messages) {
		if (!is_array($messages)) $messages = array($messages);
		if (!is_array($this->_messages)) $this->_messages = $messages;
		else $this->_messages = array_merge($this->_messages, $messages);
	}
	protected function _setError(&$process, $message) {
		if (is_array($this->_messages))
			foreach ($this->_messages as $errMessage)
				if ($errMessage->type == 'over_weight') {
					$message = 'Your shopping cart is too heavy for being shipped by DPD Relais';
					break;
				}
		$error = $this->_trackErrorFactory->create();
		$error->setCarrier($this->_code);
		$error->setCarrierTitle($this->_scopeConfig->getValue('carriers/dpdfrpredict/title'));
		$error->setErrorMessage(__($message));
		$result = $this->getResult();
		$result->append($error);
		$process['result'] = $error;
	}
	protected function _appendMethod($process, $row, $fees) {
		/** @var $method \Magento\Shipping\Model\Rate\Result */
		$method = $this->methodFactory->create()
				->setCarrier($this->_code)
				->setCarrierTitle($this->_scopeConfig->getValue('carriers/dpdfrpredict/title'))
				->setMethod($row['*code'])
				->setMethodTitle($this->_scopeConfig->getValue('carriers/dpdfrpredict/name') . ' ' . $this->_getMethodText($process,$row,'label'))
				->setPrice($fees)
				->setCost($fees);
		$process['result']->append($method);
	}
	protected function _getMethodText($process, $row, $property) {
		if (!isset($row[$property])) return '';

		return $this->_owebiaCore->evalInput($process,$row,$property,str_replace(
				array('{cart.weight}','{cart.price_including_tax}','{cart.price_excluding_tax}'),
				array(
						$process['data']['cart.weight'].$process['data']['cart.weight.unit'],
						$this->_formatPrice($process['data']['cart.price_including_tax']),
						$this->_formatPrice($process['data']['cart.price_excluding_tax'])
				),
				$this->_owebiaCore->getRowProperty($row,$property)
		));
	}
	protected function _formatPrice($price) {
		return $this->currencyHelper->currency(number_format($price, 2), true, false);
	}
	protected function _getCountryName($country_code) {
		return $this->countryModel->load($country_code)->getName();
	}

}