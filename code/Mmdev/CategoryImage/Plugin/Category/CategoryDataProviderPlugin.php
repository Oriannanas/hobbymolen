<?php
/**
 * @see PROJECT_LICENSE.txt
 */

namespace Mmdev\CategoryImage\Plugin\Category;

use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;

/**
 * CategoryDataProviderPlugin
 */
class CategoryDataProviderPlugin
{
  protected $storeManager;

  /**
   * CategoryDataProviderPlugin constructor.
   *
   * @param StoreManagerInterface $storeManager
   */
  public function __construct(
    StoreManagerInterface $storeManager
  )
  {
    $this->storeManager = $storeManager;
  }

  /**
   * AfterGetData
   *
   * we need to modify the data Array returned and generate the data array.
   * This includes the correct image url.
   * Without the information the Admin will not show this field due to an error
   *
   * @param CategoryDataProvider $subject
   * @param array $data
   *
   * @return array
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function afterGetData(CategoryDataProvider $subject, array $data)
  {
    /** @var Category $category */
    $category = $subject->getCurrentCategory();
    if (!$category) {
      return $data;
    }

    $attributeCodes = [
      'header_image',
    ];

    foreach ($attributeCodes as $attributeCode) {
      $image = $category->getData($attributeCode);
      if (!$image) {
        continue;
      }

      $imageName = $image;
      if (!is_string($image)) {
        if (is_array($image)) {
          $imageName = $image[0]['name'];
        }
      }

      $basePath    = 'catalog/category/mmdev/' . $attributeCode;
      /** @var Store $store */
      $store = $this->storeManager->getStore();
      $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
      $categoryImageUrl = $baseUrl . 'catalog/category/mmdev/' . $attributeCode . '/' . $imageName;
      $seoImageData = [
        0 => [
          'name' => $imageName,
          'url' => $categoryImageUrl,
        ],
      ];
      $data[$category->getId()][$attributeCode] = $seoImageData;
    }

    return $data;
  }
}