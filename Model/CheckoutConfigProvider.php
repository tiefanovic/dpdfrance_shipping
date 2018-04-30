<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Model;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
	const DPD_GOOGLE_MAPS_WIDTH = 'carriers/dpdpickup/map_width';
	const DPD_GOOGLE_MAPS_HEIGHT = 'carriers/dpdpickup/map_height';

	/**
	 * @var UrlInterface
	 */
	private $urlBuilder;

	private $scopeConfig;
    
    protected $assetRepo;

	public function __construct(
		UrlInterface $urlBuilder,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		AssetRepository $assetRepo
	) {
		$this->urlBuilder = $urlBuilder;
		$this->scopeConfig = $scopeConfig;
		$this->assetRepo = $assetRepo;
	}

	public function getConfig()
	{
		$output['dpd_parcelshop_url'] = $this->urlBuilder->getUrl('dpd/parcelshops', ['_secure' => true]);
		$output['dpd_parcelshop_save_url'] = $this->urlBuilder->getUrl('dpd/parcelshops/save', ['_secure' => true]);
		$output['dpd_frrelais_check'] = $this->urlBuilder->getUrl('dpd/pickup/index', ['_secure' => true]);
		$output['dpd_frrelais_marker'] = $this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/marker.png', ['_secure' => true]);
		$output['dpd_frpredict_logo'] = $this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/predict/dpd_predict_logo.png', ['_secure' => true]);
		$output['dpd_frrelais_logo'] = $this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/carrier_logo.jpg', ['_secure' => true]);
		$output['dpd_frrelais_loader'] = $this->assetRepo->getUrlWithParams('DPDFrance_Shipping::images/relais/loader.gif', ['_secure' => true]);
		$output['dpd_googlemaps_width'] = $this->scopeConfig->getValue(self::DPD_GOOGLE_MAPS_WIDTH);
		$output['dpd_googlemaps_height'] = $this->scopeConfig->getValue(self::DPD_GOOGLE_MAPS_HEIGHT);
		return $output;
	}
}