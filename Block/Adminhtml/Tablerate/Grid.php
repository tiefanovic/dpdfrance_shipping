<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Block\Adminhtml\Tablerate;

/**
 * Shipping carrier table rate grid block
 * WARNING: This grid used for export table rates
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Website filter
     *
     * @var int
     */
    protected $_websiteId;

    /**
     * Condition filter
     *
     * @var string
     */
    protected $_conditionName;

    /**
     * @var \DPDFrance\Shipping\Model\Tablerate
     */
    protected $_tablerate;

    /**
     * @var \DPDFrance\Shipping\Model\ResourceModel\Tablerate\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate\CollectionFactory $collectionFactory
     * @param \Magento\OfflineShipping\Model\Carrier\Tablerate $tablerate
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \DPDFrance\Shipping\Model\ResourceModel\Tablerate\CollectionFactory $collectionFactory,
        \DPDFrance\Shipping\Model\Tablerate $tablerate,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_tablerate = $tablerate;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Define grid properties
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('shippingTablerateGrid');
        $this->_exportPageSize = 10000;
    }

    /**
     * Set current website
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        $this->_websiteId = $this->_storeManager->getWebsite($websiteId)->getId();
        return $this;
    }

    /**
     * Retrieve current website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        if ($this->_websiteId === null) {
            $this->_websiteId = $this->_storeManager->getWebsite()->getId();
        }
        return $this->_websiteId;
    }

    /**
     * Set current website
     *
     * @param string $name
     * @return $this
     */
    public function setConditionName($name)
    {
        $this->_conditionName = $name;
        return $this;
    }

    /**
     * Retrieve current website id
     *
     * @return int
     */
    public function getConditionName()
    {
        return $this->_conditionName;
    }

    /**
     * Prepare shipping table rate collection
     *
     * @return \DPDFrance\Shipping\Block\Adminhtml\Tablerate\Grid
     */
    protected function _prepareCollection()
    {
        /** @var $collection \DPDFrance\Shipping\Model\ResourceModel\Tablerate\Collection */
        $collection = $this->_collectionFactory->create();
        $collection->setConditionFilter($this->getConditionName())->setWebsiteFilter($this->getWebsiteId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare table columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'dest_country',
            ['header' => __('Country'), 'index' => 'dest_country', 'default' => '*']
        );

        $this->addColumn(
            'dest_region',
            ['header' => __('Region/State'), 'index' => 'dest_region', 'default' => '*']
        );

        $this->addColumn(
            'dest_zip',
            ['header' => __('Zip/Postal Code'), 'index' => 'dest_zip', 'default' => '*']
        );

        $label = $this->_tablerate->getCode('condition_name_short', $this->getConditionName());
        $this->addColumn('condition_value', ['header' => $label, 'index' => 'condition_value']);

        $this->addColumn('price', ['header' => __('Shipping Price'), 'index' => 'price']);

        return parent::_prepareColumns();
    }
}
