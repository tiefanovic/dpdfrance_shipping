<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Controller\Adminhtml\Order;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\Order;


class CreateShipment extends \Magento\Backend\App\Action
{
	/**
	 * @var \Magento\Ui\Component\MassAction\Filter
	 */
	protected $filter;

	/**
	 * @var object
	 */
	protected $collectionFactory;

	/**
	 * @var \DPDFrance\Shipping\Helper\Data
	 */
	protected $dataHelper;

	/**
	 * @var FileFactory
	 */
	protected $fileFactory;

	/**
	 * @param Context $context
	 * @param Filter $filter
	 */
	public function __construct(Context $context,
								Filter $filter,
								OrderCollectionFactory $collectionFactory,
								\DPDFrance\Shipping\Helper\Data $dataHelper,
								FileFactory $fileFactory)
	{
		$this->filter = $filter;
		$this->collectionFactory = $collectionFactory;
		$this->dataHelper = $dataHelper;
		$this->fileFactory = $fileFactory;
		parent::__construct($context);
	}

	/**
	 * Execute action
	 *
	 * @return \Magento\Framework\Controller\Result\|\Magento\Framework\App\ResponseInterface
	 * @throws \Magento\Framework\Exception\LocalizedException|\Exception
	 */
	public function execute()
	{
		try
		{
			$collection = $this->collectionFactory->create();
			$collection = $this->filter->getCollection($collection);

			$labelPDFs = array();

			foreach ($collection as $order)
			{
				/** @var Order $order */
				$this->currentOrder = $order;

				if($this->dataHelper->isDPDPredictOrder($order))
				{
					$labelPDFs = array_merge($labelPDFs, $this->dataHelper->createShipment($order, false));
				}

				if($this->dataHelper->isDPDPickupOrder($order))
				{
					$labelPDFs = array_merge($labelPDFs, $this->dataHelper->createShipment($order, false));
				}

				if($this->dataHelper->isDPDSaturdayOrder($order))
				{
					$labelPDFs = array_merge($labelPDFs, $this->dataHelper->createShipment($order, true));
				}
			}


			if(count($labelPDFs) == 0)
			{
				$this->messageManager->addErrorMessage(
					__('DPD - There are no shipping labels generated.')
				);

				return $this->_redirect($this->_redirect->getRefererUrl());
			}

			$resultPDF = $this->dataHelper->combinePDFFiles($labelPDFs);

			return $this->fileFactory->create(
				'DPD-shippinglabels.pdf',
				$resultPDF,
				DirectoryList::VAR_DIR,
				'application/pdf'
			);

		} catch (\Exception $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
			return $this->_redirect($this->_redirect->getRefererUrl());
		}
	}

	/**
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	private function redirect()
	{
		$redirectPath = 'sales/order/index';

		$resultRedirect = $this->resultRedirectFactory->create();

		$resultRedirect->setPath($redirectPath);

		return $resultRedirect;
	}
}
