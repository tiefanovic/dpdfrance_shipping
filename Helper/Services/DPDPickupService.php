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

class DPDPickupService extends AbstractHelper
{
	const DPD_MAX_PARCELSHOPS = 'carriers/dpdpickup/map_max_shops';

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
	 * @param $longitude
	 * @param $latitude
	 * @return array
	 * @throws \Exception
	 */
	public function getParcelShops($latitude, $longitude)
	{
		$accessToken = $this->authenticationService->getAccessToken();
		$delisId = $this->authenticationService->getDelisId();

		$limit = $this->scopeConfig->getValue(self::DPD_MAX_PARCELSHOPS);

		$parameters = array(
			'latitude' => $latitude,
			'longitude' => $longitude,
			'limit' => $limit,
			'consigneePickupAllowed' => 'true'
		);


		$result = $this->dpdClient->findParcelShopsByGeoData($parameters, $delisId, $accessToken);
		return $result->parcelShop;
	}

}