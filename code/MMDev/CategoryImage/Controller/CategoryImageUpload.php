<?php

namespace MMDev\CategoryImage\Controller\Adminhtml\Category;

use Magento\Framework\Controller\ResultFactory;

class CategoryImageUpload extends \Magento\Backend\App\Action {
// […]

  /**
   * Upload file controller action
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {
    try {
      $attributeCode = $this->getRequest()->getParam('attribute_code');
      if (!$attributeCode) {
        throw new \Exception('attribute_code missing');
      }

      $basePath    = 'catalog/category/mmdev/' . $attributeCode;
      $baseTmpPath = 'catalog/category/mmdev/tmp/' . $attributeCode;

      $this->imageUploader->setBasePath($basePath);
      $this->imageUploader->setBaseTmpPath($baseTmpPath);

      $result = $this->imageUploader->saveFileToTmpDir($attributeCode);

      $result['cookie'] = [
        'name'     => $this->_getSession()->getName(),
        'value'    => $this->_getSession()->getSessionId(),
        'lifetime' => $this->_getSession()->getCookieLifetime(),
        'path'     => $this->_getSession()->getCookiePath(),
        'domain'   => $this->_getSession()->getCookieDomain(),
      ];
    } catch (\Exception $e) {
      $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
    }

    return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
  }
}