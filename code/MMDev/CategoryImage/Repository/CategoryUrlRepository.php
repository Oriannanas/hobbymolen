<?php
/**
 * @copyright Copyright (c) 1999-2016 netz98 GmbH (http://www.netz98.de)
 *
 * @see PROJECT_LICENSE.txt
 */

namespace MMDev\CategoryImage\Repository;

use MMDev\CategoryImage\Api\CategoryUrlRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * CategoryUrlRepository
 */
class CategoryUrlRepository implements CategoryUrlRepositoryInterface
{
  /**
   * @var StoreManagerInterface
   */
  private $storeManager;

  /**
   * CategoryUrlRepository constructor.
   *
   * @param StoreManagerInterface $storeManager
   */
  public function __construct(StoreManagerInterface $storeManager)
  {
    $this->storeManager = $storeManager;
  }

  /**
   * @param CategoryInterface $category
   * @param $attributeCode
   *
   * @return string
   */
  public function getCategoryIconUrl(CategoryInterface $category, $attributeCode)
  {
    $url = '';

    $imageAttribute = $category->getCustomAttribute($attributeCode);

    if (!$imageAttribute instanceof AttributeInterface) {
      return $url;
    }

    $imageName = $imageAttribute->getValue();

    if (!$imageName) {
      return $url;
    }

    /** @var StoreInterface $store */
    $store = $this->storeManager->getStore();
    $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    $url = $baseUrl . 'catalog/category/mmdev/' . $attributeCode . '/' . $imageName;

    return $url;
  }

}