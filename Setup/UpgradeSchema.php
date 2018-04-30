<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
namespace DPDFrance\Shipping\Setup;
 
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
	private $scopeConfig;
	private $configWriter;
	
	public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
								WriterInterface $configWriter)
	{
		$this->scopeConfig = $scopeConfig;
		$this->configWriter = $configWriter;
	}
	
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
 
        //handle all possible upgrade versions
 
        if(!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }
 
		$connection = $setup->getConnection();
          if (version_compare($context->getVersion(), '1.0.3') < 0) {

			// Code to upgrade to 1.0.3

			$tableName = $setup->getTable('quote');

			if ($connection->tableColumnExists($tableName, 'dpd_parcelshop_id') === false)
			{			
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_parcelshop_id',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop ID',
					]
				);
			}
			
			if ($connection->tableColumnExists($tableName, 'dpd_company') === false)
			{			
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_company',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop company',
					]
				);
			}
			
			if ($connection->tableColumnExists($tableName, 'dpd_street') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_street',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop street',
					]
				);
			}
			
			if ($connection->tableColumnExists($tableName, 'dpd_zipcode') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_zipcode',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop zipcode',
					]
				);
			}
			
			if ($connection->tableColumnExists($tableName, 'dpd_city') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_city',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop city',
					]
				);
			}
			
			if ($connection->tableColumnExists($tableName, 'dpd_country') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_country',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop country',
					]
				);
			}
			if ($connection->tableColumnExists($tableName, 'dpd_region') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_region',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'DPD Region',
					]
				);
			}
			if ($connection->tableColumnExists($tableName, 'gsm_dst') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'gsm_dst',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'GSM DST',
					]
				);
			}
				if ($connection->tableColumnExists($tableName, 'dpd_region_id') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_region_id',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'DPD Region Id',
					]
				);
			}
			if ($connection->tableColumnExists($tableName, 'dpd_extra_info') === false)
			{		
				$setup->getConnection()->addColumn(
					$setup->getTable('quote'),
					'dpd_extra_info',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop extra info',
					]
				);
			}
        }

		if (version_compare($context->getVersion(), '1.0.4') < 0)
		{
			if ($connection->tableColumnExists($setup->getTable('sales_order'), 'dpd_shop_id') === false)
			{
				// Code to upgrade to 2.0.4
				$setup->getConnection()->addColumn(
					$setup->getTable('sales_order'),
					'dpd_shop_id',
					[
						'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
						'nullable' => true,
						'comment' => 'Parcelshop ID',
					]
				);
			}
		}
		
		if (version_compare($context->getVersion(), '1.0.5') < 0)
		{
			// Code to upgrade to 1.0.5
			
			if(!$setup->tableExists($setup->getTable('dpd_shipping_tablerate')))
			{
				/**
				 * Create table 'dpd_shipping_tablerate'
				 */
				$table = $setup->getConnection()->newTable(
					$setup->getTable('dpd_shipping_tablerate')
				)->addColumn(
					'pk',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
					'Primary key'
				)->addColumn(
					'website_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => false, 'default' => '0'],
					'Website Id'
				)->addColumn(
					'dest_country_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					4,
					['nullable' => false, 'default' => '0'],
					'Destination coutry ISO/2 or ISO/3 code'
				)->addColumn(
					'dest_region_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable' => false, 'default' => '0'],
					'Destination Region Id'
				)->addColumn(
					'dest_zip',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					10,
					['nullable' => false, 'default' => '*'],
					'Destination Post Code (Zip)'
				)->addColumn(
					'condition_name',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					20,
					['nullable' => false],
					'Rate Condition name'
				)->addColumn(
					'condition_value',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,4',
					['nullable' => false, 'default' => '0.0000'],
					'Rate condition value'
				)->addColumn(
					'price',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,4',
					['nullable' => false, 'default' => '0.0000'],
					'Price'
				)->addColumn(
					'cost',
					\Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'12,4',
					['nullable' => false, 'default' => '0.0000'],
					'Cost'
				)->addIndex(
					$setup->getIdxName(
						'dpd_shipping_tablerate',
						['website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
						\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
					),
					['website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
					['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
				)->setComment(
					'DPD Shipping Tablerate'
				);
				$setup->getConnection()->createTable($table);
			}
		}
		
        $setup->endSetup();
    }
}