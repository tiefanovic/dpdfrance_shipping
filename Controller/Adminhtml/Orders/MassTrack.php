<?php

namespace DPDFrance\Shipping\Controller\Adminhtml\Orders;

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


class MassTrack extends \Magento\Backend\App\Action
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

    protected $converter;
    protected $trackFactory;
    protected $notifier;
	/**
	 * @param Context $context
	 * @param Filter $filter
	 */
	public function __construct(
	    Context $context,
	    Filter $filter,
	    OrderCollectionFactory $collectionFactory,
	    \Magento\Sales\Model\Convert\Order $converter,
	    \Magento\Shipping\Model\Order\TrackFactory $trackFactory,
	    \DPDFrance\Shipping\Helper\Data $dataHelper,
	    FileFactory $fileFactory)
	{
		$this->filter = $filter;
		$this->collectionFactory = $collectionFactory;
		$this->dataHelper = $dataHelper;
		$this->fileFactory = $fileFactory;
		$this->converter = $converter;
		$this->trackFactory = $trackFactory;
		parent::__construct($context);
	}


	public function execute()
	{
		try
		{
			$collection = $this->collectionFactory->create();
			$collection = $this->filter->getCollection($collection);
         	foreach ($collection as $order)
			{
			    $incrementId = $order->getIncrementID();
                $orderuniqueid = $order->getId();
                if (!$orderuniqueid) {
                    $this->messageManager->addErrorMessage(__(sprintf('La commande %s n\'existe pas', $orderId)));
                    continue;
                    }
    
                /* type of delivery */
                $type = stristr($order->getShippingMethod(),'_', true);
    
                /* depot code and shipper code determination */
                switch ($type) {
                    case 'dpdfrrelais' :
                        $depot_code = $this->dataHelper->getConfigValue('carriers/dpdfrrelais/depot', $order->getStoreId());
                        $shipper_code = $this->dataHelper->getConfigValue('carriers/dpdfrrelais/cargo', $order->getStoreId());
                        break;
                    case 'dpdfrpredict' :
                        $depot_code = $this->dataHelper->getConfigValue('carriers/dpdfrpredict/depot', $order->getStoreId());
                        $shipper_code = $this->dataHelper->getConfigValue('carriers/dpdfrpredict/cargo', $order->getStoreId());
                        break;
                    case 'dpdfrclassic' :
                        $depot_code = $this->dataHelper->getConfigValue('carriers/dpdfrclassic/depot', $order->getStoreId());
                        $shipper_code = $this->dataHelper->getConfigValue('carriers/dpdfrclassic/cargo', $order->getStoreId());
                        break;
                }
    
                /**
                 * Try to create a shipment
                 */
    
                $trackingNumber = $order->getIncrementID().'_'.$depot_code.$shipper_code;
                $trackingTitle = 'DPD France';
                $sendEmail = 1;
                $comment = 'Cher client, vous pouvez suivre l\'acheminement de votre colis par DPD en cliquant sur le lien ci-contre : '.'<a target="_blank" href="http://www.dpd.fr/tracer_'.$trackingNumber.'">Suivre ce colis DPD France</a>';
                $includeComment = 1;
    
                $shipmentId = $this->_createTracking($order, $trackingNumber, $trackingTitle, $sendEmail, $comment, $includeComment);
                if ($shipmentId != 0) {
                     $this->messageManager->addSuccessMessage(__(sprintf('Livraison %s créée pour la commande %s, statut mis à jour', $shipmentId, $incrementId, $trackingNumber)));
                }
			}

		    return $this->_redirect($this->_redirect->getRefererUrl());

		} catch (\Exception $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
			return $this->_redirect($this->_redirect->getRefererUrl());
		}
	}

	
    private function _createTracking(Order $order, $trackingNumber, $trackingTitle, $email, $comment, $includeComment)
    {
        /**
         * Check shipment creation availability
         */
        if (!$order->canShip()) {
            $this->messageManager->addErrorMessage(__(sprintf('La commande %s ne peut pas être expédiée, ou a déjà été expédiée.', $order->getRealOrderId())));
            return 0;
        }

        $shipment = $this->converter->toShipment($order);

        /**
         * Add the items to send
         */
        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip()) {
                continue;
            }
            if ($orderItem->getIsVirtual()) {
                continue;
            }

            $item = $this->converter->itemToShipmentItem($orderItem);
            $qty = $orderItem->getQtyToShip();
            $item->setQty($qty);

            $shipment->addItem($item);
        }//foreach

        $shipment->register();



        /**
         * Tracking number instanciation
         */
        $carrierCode = stristr($order->getShippingMethod(),'_', true);
        if(!$carrierCode) $carrierCode = 'custom';

        /* depot code and shipper code determination */
        switch ($carrierCode) {
            case 'dpdfrrelais' :
                $depot_code =  $this->dataHelper->getConfigValue('carriers/dpdfrrelais/depot', $order->getStoreId());
                $shipper_code =  $this->dataHelper->getConfigValue('carriers/dpdfrrelais/cargo', $order->getStoreId());
                break;
            case 'dpdfrpredict' :
                $depot_code =  $this->dataHelper->getConfigValue('carriers/dpdfrpredict/depot', $order->getStoreId());
                $shipper_code =  $this->dataHelper->getConfigValue('carriers/dpdfrpredict/cargo', $order->getStoreId());
                break;
            case 'dpdfrclassic' :
                $depot_code =  $this->dataHelper->getConfigValue('carriers/dpdfrclassic/depot', $order->getStoreId());
                $shipper_code =  $this->dataHelper->getConfigValue('carriers/dpdfrclassic/cargo', $order->getStoreId());
                break;
        }
        // Le trackingNumber est composé du n° de commande + le code agence + code cargo, intégré en un bloc dans l'URL
        $trackingNumber = $order->getIncrementID().'_'.$depot_code.$shipper_code;
        $trackingUrl = 'http://www.dpd.fr/tracer_'.$trackingNumber;

        $track = $this->trackFactory->create();
        $track->setNumber($trackingNumber)
            ->setCarrierCode($carrierCode)
            ->setTitle($trackingTitle)
            ->setUrl($trackingUrl)
            ->setStatus( '<a target="_blank" href="'.$trackingUrl.'">'.__('Suivre ce colis DPD France').'</a>' );

        $shipment->addTrack($track);

        /**
         * Comment handling
         */
        $shipment->addComment($comment, $email && $includeComment);

        /**
         * Change order status to Processing
         */
        $shipment->getOrder()->setIsInProcess(true);

        /**
         * If e-mail, set as sent (must be done before shipment object saving)
         */
        if ($email) {
            $shipment->setEmailSent(true);
        }

        try {
            /**
             * Save the created shipment and the updated order
             */
            $shipment->save();
            $shipment->getOrder()->save();

            /**
             * Email sending
             */
            //$this->notifier>notify($shipment);
        } catch (Mage_Core_Exception $e) {
            $this->messageManager->addErrorMessage(__(sprintf('Erreur pendant la création de l\'expédition %s : %s', $orderId, $e->getMessage())));
            return 0;
        }

        /**
         * Everything was ok : return Shipment real id
         */
        return $shipment->getIncrementId();

    }


}
