<?php

namespace Mmdev\Categoryimage\Observer\Category;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ImageDataPrepare
 */
class CategoryimageDataPrepare implements ObserverInterface {
  /**
   * List of available cms page image attributes
   *
   * @var []
   */
  protected $imageAttributes = [
    'header_image',
  ];

  /** @var \Magento\Catalog\Model\ImageUploader  */
  private   $imageUploader;


  /**
   * Prepare image data before save
   *
   * @param Observer $observer
   *
   * @return $this
   */
  public function execute(Observer $observer) {
    /** @var \Magento\Catalog\Model\Category $category */
    $category = $observer->getCategory();
    $data     = $observer->getRequest()->getParams();
    foreach ($this->imageAttributes as $attributeName) {
      if (isset($data[$attributeName]) && is_array($data[$attributeName])) {
        if (!empty($data[$attributeName]['delete'])) {
          $data[$attributeName] = null;
        } else {
          $basePath    = 'catalog/category/mmdev/' . $attributeName;
          $baseTmpPath = 'catalog/category/mmdev/tmp/' . $attributeName;
          $this->imageUploader->setBasePath($basePath);
          $this->imageUploader->setBaseTmpPath($baseTmpPath);

          $this->imageUploader->moveFileFromTmp($attributeName);
        }
      }
      if (isset($data[$attributeName])) {
        $category->setData($attributeName, $data[$attributeName]);
      }
    }

    return $this;
  }
}