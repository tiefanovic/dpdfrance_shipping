<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Controller\Parcelshops;

use DPDFrance\Shipping\Helper\Data;
use Magento\Framework\View\Asset\Repository;

class Save extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var \DPDFrance\Shipping\Helper\Data
	 */
	protected $data;

	/**
	 * @var \Magento\Framework\Controller\Result\JsonFactory
	 */
	protected $resultPageFactory;

	/**
	 * @var \Magento\Framework\View\Asset\Repository
	 */
	protected $assetRepo;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $checkoutSession;
	/**
	 * @var \Magento\Quote\Api\CartRepositoryInterface
	 */
	protected $quoteRepository;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		Data $data,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		Repository $assetRepo,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	)
	{
		parent::__construct($context);

		$this->data = $data;
		$this->resultPageFactory = $resultPageFactory;
		$this->assetRepo = $assetRepo;
		$this->checkoutSession = $checkoutSession;
		$this->quoteRepository = $quoteRepository;
	}

	/**
	 * Execute view action
	 *
	 * @return \Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$parcelData = $this->getRequest()->getPostValue();
		$resultPage = $this->resultPageFactory->create();
      
		$quote = $this->checkoutSession->getQuote();
		$quote->setDpdCompany($parcelData['company']) ->setDpdStreet($parcelData['street'][0]) ->setDpdZipcode($parcelData['postcode']) ->setDpdCity($parcelData['city']);

		$this->quoteRepository->save($quote);
	}
}