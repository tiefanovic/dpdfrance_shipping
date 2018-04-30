<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order;
use DPDFrance\Shipping\Helper\Services\DPDPredictService;
use \Magento\Sales\Model\Convert\Order as OrderConvert;

class Data extends AbstractHelper
{
	const DPD_GOOGLE_MAPS_API = 'carriers/dpdpickup/google_maps_api';

	/**
	 * @var DPDPredictService
	 */
	private $dpdPredictService;

	/**
	 * @var OrderConvert
	 */
	private $orderConvert;

	/**
	 * @var \Magento\Framework\DB\TransactionFactory
	 */
	private $transactionFactory;

	/**
	 * @var Order\Shipment\TrackFactory
	 */
	private $trackFactory;

	/**
	 * @var \Magento\Framework\App\Config\Storage\WriterInterface
	 */
	private $configWriter;
	
	public function __construct(\Magento\Framework\App\Helper\Context $context,
								DPDPredictService $DPDPredictService,
								OrderConvert $orderConvert,
								\Magento\Framework\DB\TransactionFactory $transactionFactory,
								\Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
								\Magento\Framework\App\Config\Storage\WriterInterface $configWriter)
	{
		$this->dpdPredictService = $DPDPredictService;
		$this->orderConvert = $orderConvert;
		$this->transactionFactory = $transactionFactory;
		$this->trackFactory = $trackFactory;
		$this->configWriter = $configWriter;
		parent::__construct($context);
	}

	public function getGoogleMapsApiKey()
	{
		return $this->scopeConfig->getValue(self::DPD_GOOGLE_MAPS_API);
	}
    public function getConfigValue($value)
	{
		return $this->scopeConfig->getValue($value);
	}
	public function createShipment(Order $order, $isDPDSaturdayOrder, Order\Shipment $shipment = null)
	{
		$includeReturnLabel = $this->scopeConfig->getValue('dpdshipping/account_settings/includeReturnLabel');

		// Check if there's a problem with the session, if that's the case, reset the timestamp so we create a new one
		try
		{

			$result = $this->dpdPredictService->storeOrders($order, $isDPDSaturdayOrder);
		}
		catch(\Exception $ex)
		{
			if(strpos($ex->getMessage(), 'De client sessie is verlopen') !== false)
			{
				$this->configWriter->save('dpdshipping/accesstoken_created', 0);
				$result = $this->dpdPredictService->storeOrders($order, $isDPDSaturdayOrder);
			}
			else
			{
				throw $ex;
			}
		}

		// If the return label option is enable we create a return label
		if($includeReturnLabel)
			$resultReturnLabel = $this->dpdPredictService->storeOrders($order, false, $includeReturnLabel);

		// If this order doesn't have a shipment we create one
		//if($order->getShipmentsCollection()->count() == 0)
		{
			$orderShipment = $this->orderConvert->toShipment($order);

			// Loop through order items
			foreach ($order->getAllItems() AS $orderItem)
			{
				$qtyShipped = $orderItem->getQtyOrdered();

				// Create shipment item with qty
				$shipmentItem = $this->orderConvert->itemToShipmentItem($orderItem);
				$shipmentItem->setQty($qtyShipped);

				// Add shipment item to shipment
				$orderShipment->addItem($shipmentItem);
			}

//				$shipment->getOrder()->setIsInProcess(true);

			$orderShipment->register();

			$shipmentTransaction = $this->transactionFactory->create()
				->addObject($orderShipment)
				->addObject($orderShipment->getOrder());
			$shipmentTransaction->save();

		}


		$shipmentCollection = $order->getShipmentsCollection();

		// if no shipment was provided we load the first one
		if($shipment == null)
		{
			// Get the first shipment because that's the one which needs a tracking number
			$shipment = $order->getShipmentsCollection()->getFirstItem();
		}

		/**
		 * @var Order\Shipment $shipment
		 */
		$carrierCode = 'dpdpredict';

		$carrierTitle = $this->scopeConfig->getValue(
			'carriers/' . $carrierCode  . '/title',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
			$shipment->getStoreId()
		);

		$shipment->addTrack(
			$this->trackFactory->create()
				->setNumber($result['parcelLabelNumber'])
				->setCarrierCode($carrierCode)
				->setTitle($carrierTitle)
		);

		$shipment->getResource()->save($shipment);

		// Merge the pdf request if a return label was found
		$pdfResult = array();
		$pdfResult[] = $result['parcellabelsPDF'];
		if($includeReturnLabel)
			$pdfResult[] = $resultReturnLabel['parcellabelsPDF'];

		return $pdfResult;
	}

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isDPDPredictOrder(Order $order)
    {
        $shippingMethod = $order->getShippingMethod();

        if($shippingMethod == 'dpdpredict_dpdpredict')
        	return true;

		return false;

    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isDPDPickupOrder(Order $order)
    {
        $shippingMethod = $order->getShippingMethod();

        if($shippingMethod == 'dpdpickup_dpdpickup')
        	return true;

		return false;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isDPDSaturdayOrder(Order $order)
    {
        $shippingMethod = $order->getShippingMethod();

        if($shippingMethod == 'dpdsaturday_dpdsaturday')
        	return true;

		return false;
    }



	/**
	 * Supply and array of binary PDF file and it'll combine them into one
	 * @param array $pdfFiles
	 */
    public function combinePDFFiles(array $pdfFiles)
	{
		$outputPdf = new \Zend_Pdf();
		foreach ($pdfFiles as $content) {
			if (stripos($content, '%PDF-') !== false) {
				$pdfLabel = \Zend_Pdf::parse($content);
				foreach ($pdfLabel->pages as $page) {
					$outputPdf->pages[] = clone $page;
				}
			}
		}
		return $outputPdf->render();
	}
}
