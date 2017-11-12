<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mmdev\hobbymolen\Setup;

use Magento\Cms\Model\Page;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface {

  /**
   * @var \Magento\Cms\Model\PageFactory
   */
  protected $_pageFactory;
  /**
   * @var \Magento\Framework\Module\Dir\Reader
   */
  protected $moduleReader;

  /**
   * Construct
   *
   * @param \Magento\Cms\Model\PageFactory $pageFactory
   */
  public function __construct(
    \Magento\Cms\Model\PageFactory $pageFactory,
    \Magento\Framework\Module\Dir\Reader $moduleReader
  ) {
    $this->_pageFactory = $pageFactory;
    $this->moduleReader = $moduleReader;
  }

  /**
   * {@inheritdoc}
   */
  public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
    $setup->startSetup();
    /** @var Page $page */

    $pages = [
      [
        'title'      => 'Over ons',
       'identifier' => 'over-ons',
      ],
      [
        'title'      => 'Algemene voorwaarden',
        'identifier' => 'algemene-voorwaarden',
      ],
      [
        'title'      => 'Privacy Policy',
        'identifier' => 'privacy-policy',
      ],
      [
        'title'      => 'F.A.Q.',
        'identifier' => 'faq',
      ],
      [
        'title'      => 'Verzenden en retour',
        'identifier' => 'verzenden-en-retour',
      ],
      [
        'title'      => 'Garantie',
        'identifier' => 'garantie',
      ],
    ];

    foreach ($pages as $pageData) {
      $page = $this->_pageFactory->create();
      $filePath = $this->getDirectory().'/'.$pageData['identifier'].'.html';
      $filePath = realpath($filePath);
      $page->setTitle($pageData['title'])
           ->setIdentifier($pageData['identifier'])
           ->setIsActive(true)
           ->setPageLayout('1column')
           ->setStores([0])
           ->setContent(file_get_contents($filePath))
           ->save();
    }

    $setup->endSetup();
  }


  public function getDirectory()
  {
    $viewDir = $this->moduleReader->getModuleDir(
      \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
      'Mmdev_hobbymolen'
    );
    return $viewDir;
  }
}
