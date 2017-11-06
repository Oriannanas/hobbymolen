<?php

namespace MMDev\CategoryWidget\Block\Widget;

class CategoryWidget extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface {
  protected $_template = 'widget/categorywidget.phtml';
  /**
   * Core registry
   *
   * @var \Magento\Framework\Registry
   */
  protected $_coreRegistry = null;

  const DEFAULT_IMAGE_WIDTH = 250;
  const DEFAULT_IMAGE_HEIGHT = 250;

  /**
   * \Magento\Catalog\Model\CategoryFactory $categoryFactory
   */
  protected $_categoryFactory;

  /**
   * @param \Magento\Framework\View\Element\Template\Context $context
   * @param \Magento\Catalog\Model\CategoryFactory           $categoryFactory
   * @param array                                            $data
   */
  public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Catalog\Model\CategoryFactory $categoryFactory
  ) {
    $this->_categoryFactory = $categoryFactory;
    $this->_coreRegistry = $registry;
    parent::__construct($context);
  }

  /**
   * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
   */
  public function getCategoryCollection() {
    $category = $this->_categoryFactory->create();

    $rootCatID = null;
    $currentCategory = $this->getCurrentCategory();
    if ($this->getData('parentcat') > 0) {
      $rootCatID = $this->getData('parentcat');
    } else if($currentCategory) {
      $rootCatID = $currentCategory->getId();
    } else {
      $rootCatID = $this->_storeManager->getStore()->getRootCategoryId();
    }
    $category->load($rootCatID);
    /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $childrenCollection */
    $childrenCollection = $category->getCollection();
    $childrenCollection->addIdFilter($category->getChildren())
                       ->addAttributeToFilter('is_active', 1)
                       ->addAttributeToSelect('name')
                       ->addAttributeToSelect('url')
                       ->addAttributeToSelect('image');

    return $childrenCollection;
  }

  /**
   * Retrieve current category model object
   *
   * @return \Magento\Catalog\Model\Category
   */
  public function getCurrentCategory()
  {
    if (!$this->hasData('current_category')) {
      $this->setData('current_category', $this->_coreRegistry->registry('current_category'));
    }
    return $this->getData('current_category');
  }

  /**
   * Get the width of product image
   * @return int
   */
  public function getImageWidth() {
    if ($this->getData('imagewidth') == '') {
      return self::DEFAULT_IMAGE_WIDTH;
    }

    return (int)$this->getData('imagewidth');
  }

  /**
   * Get the height of product image
   * @return int
   */
  public function getImageHeight() {
    if ($this->getData('imageheight') == '') {
      return self::DEFAULT_IMAGE_HEIGHT;
    }

    return (int)$this->getData('imageheight');
  }

  public function canShowImage() {
    if ($this->getData('image') == 'image')
      return true;
    else if ($this->getData('image') == 'no-image')
      return false;
  }
}