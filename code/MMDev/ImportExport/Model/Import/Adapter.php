<?php
/**
 * Created by PhpStorm.
 * User: marijn
 * Date: 11-Nov-17
 * Time: 18:02
 */

namespace MMDev\ImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\ImportExport\Model\Import\AbstractSource;

class Adapter extends \Magento\ImportExport\Model\Import\Adapter{

  public static function factory($type, $directory, $source, $options = null) {

    if (!is_string($type) || !$type) {
      throw new LocalizedException(
        __('The adapter type must be a non-empty string.')
      );
    }

    $adapterClass = '\MMDev\ImportExport\Model\Import\Source\\' . ucfirst(strtolower($type));

    if (!class_exists($adapterClass)) {
      return parent::factory($type, $directory, $source, $options);
    }
    $adapter = new $adapterClass($source, $directory, $options);

    if (!$adapter instanceof AbstractSource) {
      throw new LocalizedException(
        __('Adapter must be an instance of \Magento\ImportExport\Model\Import\AbstractSource')
      );
    }

    return $adapter;
  }

  /**
   * Create adapter instance for specified source file.
   *
   * @param string $source Source file path.
   * @param Write $directory
   * @param mixed $options OPTIONAL Adapter constructor options
   *
   * @return AbstractSource
   */
  public static function findAdapterFor($source, $directory, $options = null)
  {
    return self::factory(pathinfo($source, PATHINFO_EXTENSION), $directory, $source, $options);
  }
}