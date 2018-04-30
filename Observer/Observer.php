<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Observer;

use Magento\Directory\Model\Region;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class Observer implements ObserverInterface
{
	/**
	 * @var OrderRepository
	 */
	private $orderRepository;
	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	private $checkoutSession;

	/**
	 * @var Order\AddressRepository
	 */
	private $addressRepository;

	protected $request;
	protected $region;

	public function __construct(
		OrderRepository $orderRepository,
		Order\AddressRepository $addressRepository,
		RequestInterface $request,
		Region $region,
		\Magento\Checkout\Model\Session $checkoutSession)
	{
		$this->orderRepository = $orderRepository;
		$this->checkoutSession = $checkoutSession;
		$this->addressRepository = $addressRepository;
		$this->request = $request;
		$this->region = $region;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$quote = $observer->getEvent()->getQuote();
		$order = $observer->getEvent()->getOrder();

		//file_put_contents('quote.txt', print_r($quote->debug(), true), FILE_APPEND);
		//file_put_contents('order.txt', print_r($order->debug(), true), FILE_APPEND);

		/** @var Order $order */
		if(stristr($order->getShippingMethod(), '_', true) === 'dpdfrrelais')
		{
			//Get Object Manager Instance
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

			//Load product by product id
			$order = $objectManager->create('Magento\Sales\Model\Order')->load($order->getId());

			$shippingAddress = $order->getShippingAddress();
			
			
            if (substr($quote->getDpdZipcode(),0,2) == 20) // Récupération de la région de destination : Tri spécial sur le code postal pour la Corse, permettant de séparer 2A et 2B
                {
                    $regioncode = substr($quote->getDpdZipcode(),0,3);
                    switch ($regioncode) {
                        case 200 :
                        case 201 :
                            $regioncode = '2A';
                            break;
                        case 202 :
                        case 206 :
                            $regioncode = '2B';
                            break;
                    }

                } else { // Si pas en Corse, récupérer les 2 premiers chiffres du CP pour trouver la région.
                    $regioncode = substr($quote->getDpdZipcode(),0,2);
            }
             if (substr($regioncode,0,1) == 0) {
                    $region = $this->region->loadByCode($regioncode,$shippingAddress->getCountryId());
                    if ($region->getName() == '') {
                        $region = $this->region->loadByCode(substr($regioncode,1,1),$shippingAddress->getCountryId());
                    }
                }else{
                    $region = $this->region->loadByCode($regioncode,$shippingAddress->getCountryId());
                }

            $regionname = $region->getName();
            $regionid = $region->getRegionId();
			$shippingAddress->setCompany($quote->getDpdCompany());
			$shippingAddress->setRegion($quote->getDpdRegion());
			$shippingAddress->setRegionId($quote->getDpdRegionId());
			$shippingAddress->setStreet($quote->getDpdStreet());
			$shippingAddress->setCity($quote->getDpdCity());
			$shippingAddress->setPostcode($quote->getDpdZipcode());
			$shippingAddress->save();
		}
        if(stristr($order->getShippingMethod(), '_', true) === 'dpdfrpredict') { 
            			//Get Object Manager Instance
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

			//Load product by product id
			$order = $objectManager->create('Magento\Sales\Model\Order')->load($order->getId());

			$shippingAddress = $order->getShippingAddress();
			
            $input_tel = $quote->getGsmDst(); 
            if($input_tel !='') { 

                $gsm = str_replace(array(' ', '.', '-', ',', ';', '/', '\\', '(', ')'),'',$input_tel);
                $gsm = str_replace('+33','0',$gsm);
                if (!(bool)preg_match('/^((\+33|0)[67])(?:[ _.-]?(\d{2})){4}$/', $gsm, $res)){ 
                }else{
                    $shippingAddress->setTelephone($gsm);
                    $shippingAddress->save();
                }
            }
        }


    }
}