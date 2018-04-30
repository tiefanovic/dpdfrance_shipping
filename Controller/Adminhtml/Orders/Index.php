<?php

namespace DPDFrance\Shipping\Controller\Adminhtml\Orders;

class Index extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('DPDFrance_Shipping::order_management');
        $resultPage->addBreadcrumb(__('Order Management'), __('Order Management'));
		$resultPage->getConfig()->getTitle()->prepend((__('Orders Management')));

		return $resultPage;
	}
   
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('DPDFrance_Shipping::order_management');
    }

}