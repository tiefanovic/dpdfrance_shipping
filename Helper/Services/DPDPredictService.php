<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Helper\Services;

use Magento\Framework\App\Helper\AbstractHelper;
use DPDFrance\Shipping\Helper\DPDClient;

class DPDPredictService extends AbstractHelper
{
	const DPD_PRINT_FORMAT = 'dpdshipping/account_settings/print_format';

	/**
	 * Used to access the accesstoken, depot and delisId
	 * @var AuthenticationService
	 */
	private $authenticationService;

	private $dpdClient;

	public function __construct(\Magento\Framework\App\Helper\Context $context,
								\DPDFrance\Shipping\Helper\Services\AuthenticationService $authenticationService,
								DPDClient $DPDClient

	)
	{
		$this->authenticationService = $authenticationService;
		$this->dpdClient = $DPDClient;
		parent::__construct($context);
	}

	/**
	 * @param \Magento\Sales\Model\Order $order
	 * @param bool $isDpdSaturday
	 * @param bool $isReturn
	 */
	public function storeOrders(\Magento\Sales\Model\Order $order, $isDpdSaturday, $isReturn = false)
	{
		$accessToken = $this->authenticationService->getAccessToken();
		$delisId = $this->authenticationService->getDelisId();
		$depot = $this->authenticationService->getDepot();

		$senderData = $this->getSenderData();
		if($senderData['zipCode'] == '' || $senderData['street'] == '' || $senderData['city'] == '')
			throw new \Exception(__('[DPD] Your store address is empty. Please open the configuration and set an address'));
		$receiverData = $this->getReceiverData($order);
		$orderWeight = $this->getOrderWeight($order);
		$predictEmail = $this->getPredictEmail($order);

		$shipmentData = [
			'printOptions' => [
				'printerLanguage' => 'PDF',
				'paperFormat' => $this->scopeConfig->getValue(self::DPD_PRINT_FORMAT),
			],
			'order' => [
				'generalShipmentData' => [
					'sendingDepot' => $depot,
					'product' => 'CL',
					'sender' => $senderData,
					'recipient' => $receiverData,
				],
				'parcels' => [
					'customerReferenceNumber1' => $order->getIncrementId(),
					'customerReferenceNumber2' => $order->getDpdShopId(),
					'weight' => $orderWeight,
					'returns' => $isReturn,
				],
				'productAndServiceData' => [
					'orderType' => 'consignment',
					'saturdayDelivery' => $isDpdSaturday,
					'predict' => [
						'channel' => 1,
						'value' => $predictEmail,
					]
				]
			],
		];

		$result = $this->dpdClient->storeOrders($shipmentData, $delisId, $accessToken);
		return [
			'parcellabelsPDF' => $result->orderResult->parcellabelsPDF,
			'parcelLabelNumber' => $result->orderResult->shipmentResponses->parcelInformation->parcelLabelNumber,
			];
	}

	public function getSenderData()
	{
		$name = $this->scopeConfig->getValue('dpdshipping/sender_address/name1');
		$street = $this->scopeConfig->getValue('dpdshipping/sender_address/street');
		$houseNo = $this->scopeConfig->getValue('dpdshipping/sender_address/houseNo');
		$zipCode = $this->scopeConfig->getValue('dpdshipping/sender_address/zipCode');
		$city = $this->scopeConfig->getValue('dpdshipping/sender_address/city');
		$country = $this->scopeConfig->getValue('dpdshipping/sender_address/country');
		
		return [
			'name1' => $name,
			'street' => $street,
			'houseNo' => $houseNo,
			'country' => $country,
			'zipCode' => $zipCode,
			'city' => $city,
		];
	}

	public function getReceiverData(\Magento\Sales\Model\Order $order)
	{
		$shippingAddress = $order->getShippingAddress();

		$street = $shippingAddress->getStreet();
		$fullStreet = implode(' ', $street);

		$recipient = array(
			'name1'             => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
			'name2'      => $shippingAddress->getCompany(),
			'street'           => $fullStreet,
			'houseNo'          => '',
			'zipCode'          => strtoupper(str_replace(' ', '', $shippingAddress->getPostcode())),
			'city'             => $shippingAddress->getCity(),
			'country'      => $shippingAddress->getCountryId(),
		);

		return $recipient;
	}

	public function getOrderWeight(\Magento\Sales\Model\Order $order)
	{
		$orderWeight = $order->getWeight();

		$weightUnit = $this->scopeConfig->getValue('general/locale/weight_unit');
		if($weightUnit == '')
			$weightUnit = 'lbs';

		if($weightUnit == 'lbs')
			$orderWeight *=  0.45359237;

		// Weight is in KG so multiply with 100
		$orderWeight *= 100;

		if($orderWeight == 0)
			$orderWeight = 600;

		return $orderWeight;
	}

	public function getPredictEmail(\Magento\Sales\Model\Order $order)
	{
		return $order->getCustomerEmail();
	}
}