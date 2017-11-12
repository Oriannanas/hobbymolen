<?php
/**
 * @copyright: Copyright © 2015 Mmdev Studio. All rights reserved.
 * @author: Mmdev Studio <fbeardev@gmail.com>
 * TODO: check replace customer_composite. Now it is the same as add/update.
 */

namespace Mmdev\ImportExport\Model;

use Mmdev\ImportExport\Model\Import\Adapter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;

class Import extends \Magento\ImportExport\Model\Import {

    /**
     * Limit displayed errors on Import History page.
     */
    const LIMIT_VISIBLE_ERRORS = 5;

    const CREATE_ATTRIBUTES_CONF_PATH = 'mmdev_importexport/general/create_attributes';

    /**
     * @var \Mmdev\ImportExport\Model\Source\ConfigInterface
     */
    protected $_config;

    /**
     * @var \Mmdev\ImportExport\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_timezone;

    /**
     * @var \Mmdev\ImportExport\Model\Source\Type\AbstractType
     */
    protected $_source;

    /**
     * @param \Mmdev\ImportExport\Model\Source\ConfigInterface        $config
     * @param \Mmdev\ImportExport\Helper\Data                         $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface       $timezone
     * @param \Psr\Log\LoggerInterface                                   $logger
     * @param \Magento\Framework\Filesystem                              $filesystem
     * @param \Magento\ImportExport\Helper\Data                          $importExportData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface         $coreConfig
     * @param \Magento\ImportExport\Model\Import\ConfigInterface         $importConfig
     * @param \Magento\ImportExport\Model\Import\Entity\Factory          $entityFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data      $importData
     * @param \Magento\ImportExport\Model\Export\Adapter\CsvFactory      $csvFactory
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory        $httpFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory           $uploaderFactory
     * @param \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry                 $indexerRegistry
     * @param \Magento\ImportExport\Model\History                        $importHistoryModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                $localeDate
     * @param array                                                      $data
     */
    public function __construct(
        \Mmdev\ImportExport\Model\Source\ConfigInterface $config,
        \Mmdev\ImportExport\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\ImportExport\Model\Import\ConfigInterface $importConfig,
        \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\ImportExport\Model\History $importHistoryModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        array $data = []
    ) {
        $this->_config = $config;
        $this->_helper = $helper;
        $this->_timezone = $timezone;

        parent::__construct(
            $logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $importHistoryModel,
            $localeDate,
            $data
        );

        $this->_debugMode = (bool) $this->_coreConfig->getValue(
            'mmdev_importexport/general/debug',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare source type class name
     *
     * @param $sourceType
     * @return string
     */
    protected function _prepareSourceClassName($sourceType)
    {
        return 'Mmdev\ImportExport\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param $timestamp
     * @return bool
     */
    public function checkModified($timestamp)
    {
        if ($this->getSource()) {
            return $this->getSource()->checkModified($timestamp);
        }

        /**
         * @TODO: check if file on current server was modified since the last import process
         */
        if ($this->getImportSource() == 'file') {

        }

        return true;
    }

    /**
     * Download remote source file to temporary directory
     *
     * @TODO change the code to show exceptions on frontend instead of 503 error.
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        $result = null;

        if ($this->getImportSource() && $this->getImportSource() != 'file') {
            $source = $this->getSource();

            try {
                $result = $source->uploadSource();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        } else {
            $uploader = $this->_uploaderFactory->create(['fileId' => self::FIELD_NAME_SOURCE_FILE]);
            $extension = $uploader->getFileExtension();
            if ($extension == 'tar') {
                $uploader->skipDbProcessing(true);
                $archiveData = $uploader->save($this->getWorkingDir());
                $phar        = new \PharData($archiveData['path'] . $archiveData['file']);
                $phar->extractTo($archiveData['path'], null, true);
                $fileName = $phar->getFilename();
                $result   = $result['path'] . $fileName;
            }

        }

        if ($result) {
            $sourceFileRelative = $this->_varDirectory->getRelativePath($result);
            $entity             = $this->getEntity();
            $this->createHistoryReport($sourceFileRelative, $entity);

            return $result;
        }

        return parent::uploadSource();
    }

    /**
     * Validates source file and returns validation result.
     *
     * @param \Magento\ImportExport\Model\Import\AbstractSource $source
     * @return bool
     */
    public function validateSource(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $this->addLogComment(__('Begin data validation'));
        try {
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $errorAggregator = $adapter->validateData();
        } catch (\Exception $e) {
            $errorAggregator = $this->getErrorAggregator();
            $errorAggregator->addError(
                \Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION
                    . '. ' . $e->getMessage(),
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->addLogComment($messages);

        $result = !$errorAggregator->getErrorsCount();
        if ($result) {
            $this->addLogComment(__('Import data validation is complete.'));
        } else {
            if ($this->isReportEntityType()) {
                $this->importHistoryModel->load($this->importHistoryModel->getLastItemId());
                $summary = '';
                if ($errorAggregator->getErrorsCount() > self::LIMIT_VISIBLE_ERRORS) {
                    $summary = __('Too many errors. Please check your debug log file.') . '<br />';
                } else {
                    if ($this->getJobId()) {
                        $summary = __('Import job #' . $this->getJobId() . ' failed.') . '<br />';
                    }

                    foreach ($errorAggregator->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
                        $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
                        $summary .= $error . '<br />';
                    }
                }
                $date = $this->_timezone->formatDateTime(
                    new \DateTime(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM,
                    null,
                    null
                );
                $summary .= '<i>' . $date . '</i>';
                $this->importHistoryModel->setSummary($summary);
                $this->importHistoryModel->setExecutionTime(\Magento\ImportExport\Model\History::IMPORT_FAILED);
                $this->importHistoryModel->save();
            }
        }
        return $result;
    }

  /**
   * Returns source adapter object.
   *
   * @param string $sourceFile Full path to source file
   * @return \Magento\ImportExport\Model\Import\AbstractSource
   */
  protected function _getSourceAdapter($sourceFile)
  {
    return Adapter::findAdapterFor(
      $sourceFile,
      $this->_filesystem->getDirectoryWrite(DirectoryList::ROOT),
      $this->getData(self::FIELD_FIELD_SEPARATOR)
    );
  }
    /**
     * @return \Mmdev\ImportExport\Model\Source\Type\AbstractType
     */
    public function getSource()
    {
        if (!$this->_source) {
            $sourceType = $this->getImportSource();
            try {
                $this->_source = $this->_helper->getSourceModelByType($sourceType);
                $this->_source->setData($this->getData());
            } catch(\Exception $e) {

            }
        }

        return $this->_source;
    }

    /**
     * @return mixed
     */
    public function getImportHistoryModel()
    {
        return $this->importHistoryModel;
    }

    /**
     * createHistoryReport
     *
     * @param string $sourceFileRelative
     * @param string $entity
     * @param null   $extension
     * @param null   $result
     *
     * @return $this
     */
    public function createHistoryReport($sourceFileRelative, $entity, $extension = null, $result = null){
        return parent::createHistoryReport($sourceFileRelative, $entity, $extension = null, $result = null);
    }
}