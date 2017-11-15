<?php

namespace Mmdev\Categoryimage\Observer\Category;

use Magento\Framework\App\ObjectManager;
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
  private $imageUploader;

  public function __construct() {
    $this->imageUploader = ObjectManager::getInstance()->get(
      'Magento\Catalog\CategoryImageUpload'
    );
  }

  /**
   * Get image uploader
   *
   * @return \Magento\Catalog\Model\ImageUploader
   *
   * @deprecated
   */
  private function getImageUploader()
  {
    if ($this->imageUploader === null) {
    }
    return $this->imageUploader;
  }
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
        } else if(!$category->getData($attributeName)){
          $basePath    = 'catalog/category/mmdev/' . $attributeName;
          $baseTmpPath = 'catalog/category/mmdev/tmp/' . $attributeName;
          $this->imageUploader->setBasePath($basePath);
          $this->imageUploader->setBaseTmpPath($baseTmpPath);
          foreach($data[$attributeName] as $image) {
            $this->imageUploader->moveFileFromTmp($image['name']);
          }
        }
      }
      if (!empty($data[$attributeName])) {
        $category->setData($attributeName, $data[$attributeName][0]['name']);
      }
    }

    return $this;
  }
}