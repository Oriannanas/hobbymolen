<?php

/**
 * TODO: map 'custom_options' field.
 * @see \Magento\CatalogImportExport\Model\Import\Product\Option::_getMultiRowFormat()
 *
 * TODO: map stock field.
 * @see \Magento\CatalogImportExport\Model\Import\Product::_saveStockItem()
 */

namespace Mmdev\ImportExport\Model\Import;

use Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor;
use Magento\Framework\Stdlib\DateTime;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProduct;
use Magento\ImportExport\Model\Import;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;
use Mmdev\ImportExport\Logger\Logger;

class Product extends MagentoProduct
{

    /**
     * Default website id
     */
    const DEFAULT_WEBSITE_ID = 1;

    /**
     * Used when create new attributes in column name
     */
    const ATTRIBUTE_SET_GROUP = 'attribute_set_group';

    /**
     * Attribute sets column name
     */
    const ATTRIBUTE_SET_COLUMN = 'attribute_set';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * @var \Mmdev\ImportExport\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Mmdev\ImportExport\Model\Source\Type\AbstractType
     */
    protected $_sourceType;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Eav\Model\EntityFactory
     */
    protected $eavEntityFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var array
     */
    protected $_attributeSetGroupCache;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var Logger
     */
    protected $importLogger;

    /**
     * Product constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Mmdev\ImportExport\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Import\Config $importConfig
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param MagentoProduct\OptionFactory $optionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param MagentoProduct\Type\Factory $productTypeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac
     * @param DateTime\TimezoneInterface $localeDate
     * @param DateTime $dateTime
     * @param Logger $importLogger
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param MagentoProduct\StoreResolver $storeResolver
     * @param MagentoProduct\SkuProcessor $skuProcessor
     * @param MagentoProduct\CategoryProcessor $categoryProcessor
     * @param MagentoProduct\Validator $validator
     * @param ObjectRelationProcessor $objectRelationProcessor
     * @param TransactionManagerInterface $transactionManager
     * @param TaxClassProcessor $taxClassProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Mmdev\ImportExport\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        Logger $importLogger,
        LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->_request = $request;
        $this->_helper = $helper;
        $this->attributeFactory = $attributeFactory;
        $this->eavEntityFactory = $eavEntityFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->productHelper = $productHelper;
        $this->importLogger = $importLogger;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data
        );
    }

//    /**
//     * Initialize source type model
//     *
//     * @param $type
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    protected function _initSourceType($type)
//    {
//        if (!$this->_sourceType) {
//            $this->_sourceType = $this->_helper->getSourceModelByType($type);
//            $this->_sourceType->setData($this->_parameters);
//        }
//    }

//    /**
//     * Import images via initialized source type
//     *
//     * @param $bunch
//     * @return mixed
//     */
//    protected function prepareImagesFromSource($bunch)
//    {
//        foreach ($bunch as &$rowData) {
//            $rowData = $this->customFieldsMapping($rowData);
//            foreach ($this->_imagesArrayKeys as $image) {
//                if (empty($rowData[$image])) {
//                    continue;
//                }
//                $dispersionPath =
//                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
//                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
//                foreach ($importImages as $importImage) {
//                    $imageSting = mb_strtolower(
//                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
//                    );
//
//                    if ($this->_sourceType) {
//                        $this->_sourceType->importImage($importImage, $imageSting);
//                    }
//                    $rowData[$image] = $this->_sourceType->getCode() . $imageSting;
//                }
//            }
//        }
//        return $bunch;
//    }

//    /**
//     * Retrieving images from all columns and rows
//     *
//     * @param $bunch
//     * @return array
//     */
//    protected function getBunchImages($bunch)
//    {
//        $allImagesFromBunch = [];
//        foreach ($bunch as $rowData) {
//            $rowData = $this->customFieldsMapping($rowData);
//            foreach ($this->_imagesArrayKeys as $image) {
//                if (empty($rowData[$image])) {
//                    continue;
//                }
//                $dispersionPath =
//                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
//                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
//                foreach ($importImages as $importImage) {
//                    $imageSting = mb_strtolower(
//                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
//                    );
//                    /**
//                     * TODO: check source type 'file'. Compare code with default Magento\CatalogImportExport\Model\Import\Product
//                     */
//                    if (isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
//                        $allImagesFromBunch[$this->_sourceType->getCode() . $imageSting] = $imageSting;
//                    } else {
//                        $allImagesFromBunch[$importImage] = $imageSting;
//                    }
//                }
//            }
//        }
//        return $allImagesFromBunch;
//    }

    /**
     * Convert attribute string syntax to array.
     *
     * @param $columnData
     *
     * @return array
     * @throws \Exception
     */
//    protected function prepareAttributeData($columnData)
//    {
//        $result = [];
//        foreach ($columnData as $field) {
//            $field = explode(':', $field);
//            if (isset($field[1])) {
//                if (preg_match('/^(frontend_label_)[0-9]+/', $field[0])) {
//                    $result['frontend_label'][intval(substr($field[0], -1))] = $field[1];
//                } else {
//                    $result[$field[0]] = $field[1];
//                }
//            }
//        }
//
//        if (!empty($result)) {
//            $attributeCode = isset($result['attribute_code']) ? $result['attribute_code']:null;
//            $frontendLabel = $result['frontend_label'][0];
//            $attributeCode = $attributeCode ?: $this->generateAttributeCode($frontendLabel);
//            $result['attribute_code'] = $attributeCode;
//
//            $entityTypeId = $this->eavEntityFactory->create()->setType(
//                \Magento\Catalog\Model\Product::ENTITY
//            )->getTypeId();
//            $result['entity_type_id'] = $entityTypeId;
//            $result['is_user_defined'] = 1;
//        }
//
//        return $result;
//    }

//    /**
//     * Generate code from label
//     *
//     * @param string $label
//     * @return string
//     */
//    protected function generateAttributeCode($label)
//    {
//        $code = substr(
//            preg_replace(
//                '/[^a-z_0-9]/',
//                '_',
//                $this->productUrl->formatUrlKey($label)
//            ),
//            0,
//            30
//        );
//        $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
//        if (!$validatorAttrCode->isValid($code)) {
//            $code = 'attr_' . ($code ?: substr(md5(time()), 0, 8));
//        }
//        return $code;
//    }

//    /**
//     * Custom fields mapping for changed purposes of fields and field names.
//     *
//     * @param array $rowData
//     *
//     * @return array
//     */
//    private function customFieldsMapping($rowData)
//    {
//        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
//            if (array_key_exists($fileFieldName, $rowData)) {
//                $rowData[$systemFieldName] = $rowData[$fileFieldName];
//            }
//        }
//
//        $rowData = $this->_parseAdditionalAttributes($rowData);
//
//        $rowData = $this->setStockUseConfigFieldsValues($rowData);
//        if (array_key_exists('status', $rowData)
//            && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
//        ) {
//            if ($rowData['status'] == 'yes') {
//                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
//            } elseif (!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
//                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
//            }
//        }
//        return $rowData;
//    }
//
//    /**
//     * Parse attributes names and values string to array.
//     *
//     * @param array $rowData
//     *
//     * @return array
//     */
//    private function _parseAdditionalAttributes($rowData)
//    {
//        if (empty($rowData['additional_attributes'])) {
//            return $rowData;
//        }
//
//        $valuePairs = explode(
//            $this->getMultipleValueSeparator(),
//            $rowData['additional_attributes']
//        );
//        foreach ($valuePairs as $valuePair) {
//            $separatorPosition = strpos($valuePair, self::PAIR_NAME_VALUE_SEPARATOR);
//            if ($separatorPosition !== false) {
//                $key = substr($valuePair, 0, $separatorPosition);
//                $value = substr(
//                    $valuePair,
//                    $separatorPosition + strlen(self::PAIR_NAME_VALUE_SEPARATOR)
//                );
//                $rowData[$key] = $value === false ? '' : $value;
//            }
//        }
//        return $rowData;
//    }
//
//    /**
//     * Set values in use_config_ fields.
//     *
//     * @param array $rowData
//     *
//     * @return array
//     */
//    private function setStockUseConfigFieldsValues($rowData)
//    {
//        $useConfigFields = array();
//        foreach ($rowData as $key => $value) {
//            if (isset($this->defaultStockData[$key]) && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key]) && !empty($value)) {
//                $useConfigFields[self::INVENTORY_USE_CONFIG_PREFIX . $key] = ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
//            }
//        }
//        $rowData = array_merge($rowData, $useConfigFields);
//        return $rowData;
//    }
//
//    /**
//     * Validate data
//     *
//     * @return ProcessingErrorAggregatorInterface
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    public function validateData()
//    {
//        if (!$this->_dataValidated) {
//            $this->getErrorAggregator()->clear();
//            // do all permanent columns exist?
//            $absentColumns = array_diff($this->replaceFields($this->_permanentAttributes), $this->getSource()->getColNames());
//            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);
//
//            // check attribute columns names validity
//            $columnNumber = 0;
//            $emptyHeaderColumns = [];
//            $invalidColumns = [];
//            $invalidAttributes = [];
//            foreach ($this->getSource()->getColNames() as $columnName) {
//                $this->importLogger->debug('Checked column '.$columnNumber);//temp
//                $columnNumber++;
//                if (!$this->isAttributeParticular($columnName)) {
//
//                    /**
//                     * Check syntax when attribute should be created on the fly
//                     */
//                    $createValuesAllowed = (bool) $this->scopeConfig->getValue(
//                        \Mmdev\ImportExport\Model\Import::CREATE_ATTRIBUTES_CONF_PATH,
//                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
//                    );
//                    $isNewAttribute = false;
//
//                    if ($createValuesAllowed && preg_match('/^(attribute\|).+/', $columnName)) {
//                        $isNewAttribute = true;
//                        $columnData = explode('|', $columnName);
//                        $columnData = $this->prepareAttributeData($columnData);
//                        $attribute = $this->attributeFactory->create();
//                        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $columnData['attribute_code']);
//                        if (!$attribute->getId()) {
//                            $attribute->setBackendType($attribute->getBackendTypeByInput($columnData['frontend_input']));
//                            $defaultValueField = $attribute->getDefaultValueByInput($columnData['frontend_input']);
//                            if (!$defaultValueField && isset($columnData['default_value'])) {
//                                unset($columnData['default_value']);
//                            }
//                            $columnData['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
//                                $columnData['frontend_input']
//                            );
//                            $columnData['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
//                                $columnData['frontend_input']
//                            );
//
//                            $attribute->addData($columnData);
//                            try {
//                                $attribute->save();
//                            } catch (\Exception $e) {
//                                $invalidColumns[] = $columnName;
//                            }
//
//                            $attributeSetCodes = explode(',', $columnData[self::ATTRIBUTE_SET_COLUMN]);
//                            foreach ($attributeSetCodes as $attributeSetCode) {
//                                if (isset($this->_attrSetNameToId[$attributeSetCode])) {
//                                $attributeSetId = $this->_attrSetNameToId[$attributeSetCode];
//                                $attributeGroupCode = isset($columnData[self::ATTRIBUTE_SET_GROUP]) ? $columnData[self::ATTRIBUTE_SET_GROUP] : 'product-details';
//                                if (!isset($this->_attributeSetGroupCache[$attributeSetId])) {
//                                    $groupCollection = $this->groupCollectionFactory->create()->setAttributeSetFilter($attributeSetId)->load();
//                                    foreach ($groupCollection as $group) {
//                                        $this->_attributeSetGroupCache[$attributeSetId][$group->getAttributeGroupCode()] = $group->getAttributeGroupId();
//                                    }
//                                }
//
//                                foreach ($this->_attributeSetGroupCache[$attributeSetId] as $groupCode => $groupId) {
//                                    if ($groupCode == $attributeGroupCode) {
//                                        $attribute->setAttributeSetId($attributeSetId);
//                                        $attribute->setAttributeGroupId($groupId);
//                                        try {
//                                            $attribute->save();
//                                        } catch (\Exception $e) {
//
//                                        }
//                                        break;
//                                    }
//                                }
//                                }
//                            }
//                        }
//                    }
//
//                    if (trim($columnName) == '') {
//                        $emptyHeaderColumns[] = $columnNumber;
//                    } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $columnName) && !$isNewAttribute) {
//                        $invalidColumns[] = $columnName;
//                    } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
//                        $invalidAttributes[] = $columnName;
//                    }
//                }
//            }
//            $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
//            $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
//            $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
//
//            $this->importLogger->debug('Finish checking columns');//temp
//            $this->importLogger->debug('Errors count: ' . $this->getErrorAggregator()->getErrorsCount());//temp
//            if (!$this->getErrorAggregator()->getErrorsCount()) {
//                $this->importLogger->debug('Start saving bunches');//temp
//                $this->mergeFieldsMap();
//                $this->_saveValidatedBunches();
//                $this->importLogger->debug('Finish saving bunches');//temp
//                $this->_dataValidated = true;
//            }
//        }
//        return $this->getErrorAggregator();
//    }

//    /**
//     * Add custom field mapping.
//     * $this->_fieldsMap – system magento mapping. Merge it with custom admin mapping.
//     *
//     * @see \Mmdev\ImportExport\Block\Adminhtml\Job\Edit\Tab\Map
//     */
//    protected function mergeFieldsMap()
//    {
//        if (isset($this->_parameters['map'])) {
//            $newAttributes = [];
//
//            foreach ($this->_parameters['map'] as $field) {
//
//                $attributeCode = $field['system'];
//
//                if (is_numeric($field['system'])) {
//                    $attribute = $this->getResource()->getAttribute((int)$field['system']);
//                    $attributeCode = $attribute->getAttributeCode();
//                }
//
//                $newAttributes[$attributeCode] = $field['import'];
//            }
//
//            $this->_fieldsMap = array_merge($this->_fieldsMap, $newAttributes);
//        }
//    }
//
//    protected function replaceFields($fields)
//    {
//        $newAttributes = [];
//
//        if (isset($this->_parameters['map'])) {
//            $mapAttributes = $newAttributes = [];
//
//            foreach ($this->_parameters['map'] as $field) {
//                $attributeCode = $field['system'];
//
//                if (is_numeric($field['system'])) {
//                    $attribute = $this->getResource()->getAttribute((int)$field['system']);
//                    $attributeCode = $attribute->getAttributeCode();
//                }
//
//                $mapAttributes[$attributeCode] = $field['import'];
//            }
//
//            foreach ($fields as $field) {
//                if (isset($field, $mapAttributes) && isset($mapAttributes[$field])) {
//                    $newAttributes[] = $mapAttributes[$field];
//                } else {
//                    $newAttributes[] = $field;
//                }
//            }
//        }
//
//        return $newAttributes ? $newAttributes : $fields;
//    }

//    public function getSpecialAttributes()
//    {
//        return $this->_specialAttributes;
//    }

    /**
     * @param $productTypeModel
     * @param $rowData
     *
     * @return mixed
     */
//    public function createAttributeValues($productTypeModel, $rowData)
//    {
//        $options = [];
//        $attributeSet = $rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_ATTR_SET];
//        foreach ($rowData as $attrCode => $attrValue) {
//            /**
//             * Add attribute to set & set's group
//             */
//            if (preg_match('/^(attribute\|).+/', $attrCode)) {
//                $columnData = explode('|', $attrCode);
//                $columnData = $this->prepareAttributeData($columnData);
//                $rowData[$columnData['attribute_code']] = $rowData[$attrCode];
//                unset($rowData[$attrCode]);
//                $attrCode = $columnData['attribute_code'];
//            }
//
//            /**
//             * Prepare new values
//             */
//            $attrParams = $productTypeModel->retrieveAttribute($attrCode, $attributeSet);
//
//            if (!empty($attrParams)) {
//                if (!$attrParams['is_static'] && isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {
//                    switch ($attrParams['type']) {
//                        case 'select':
//                            if (!isset($attrParams['options'][strtolower($rowData[$attrCode])])) {
//                                $options[$attrParams['id']][] = [
//                                    'sort_order'    => count($attrParams['options']) + 1,
//                                    'value'         => $rowData[$attrCode],
//                                    'code'          => $attrCode
//                                ];
//                            }
//                            break;
//                        case 'multiselect':
//                            foreach (explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]) as $value) {
//                                if (!isset($attrParams['options'][strtolower($value)])) {
//                                    $options[$attrParams['id']][] = [
//                                        'sort_order'    => count($attrParams['options']) + 1,
//                                        'value'         => $value,
//                                        'code'          => $attrCode
//                                    ];
//                                }
//                            }
//                            break;
//                        default: break;
//                    }
//                }
//            }
//        }
//
//        /**
//         * Create new values
//         */
//        if (!empty($options)) {
//            foreach ($options as $attributeId => $optionsArray) {
//                foreach ($optionsArray as $option) {
//                    /**
//                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOption()
//                     */
//                    $connection = $this->_connection;
//                    $resource = $this->_resourceFactory->create();
//                    $table = $resource->getTable('eav_attribute_option');
//                    $data = ['attribute_id' => $attributeId, 'sort_order' => $option['sort_order']];
//                    $connection->insert($table, $data);
//                    $intOptionId = $connection->lastInsertId($table);
//                    /**
//                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOptionValues()
//                     */
//                    $table = $resource->getTable('eav_attribute_option_value');
//                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $option['value']];
//                    $connection->insert($table, $data);
//                    $productTypeModel->addAttributeOption($option['code'], strtolower($option['value']), $intOptionId);
//                }
//            }
//        }
//
//        return $rowData;
//    }
}