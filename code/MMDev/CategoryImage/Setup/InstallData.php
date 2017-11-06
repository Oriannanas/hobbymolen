<?php

namespace MMDev\CatagoryImage\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {

  /**
   * EAV setup factory
   *
   * @var EavSetupFactory
   */
  private $eavSetupFactory;

  /**
   * Init
   *
   * @param EavSetupFactory $eavSetupFactory
   */
  public function __construct(EavSetupFactory $eavSetupFactory) {
    $this->eavSetupFactory = $eavSetupFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
    $setup->startSetup();
    /** @var EavSetup $eavSetup */
    $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

    $eavSetup->addAttribute(
      Category::ENTITY,
      'header_image',
      [
        'type'         => 'varchar',
        'label'        => 'Header image',
        'input'        => 'image',
        'sort_order'   => 333,
        'source'       => '',
        'global'       => 2,
        'visible'      => true,
        'required'     => false,
        'user_defined' => false,
        'default'      => null,
      ]
    );

    $setup->endSetup();
  }
}
