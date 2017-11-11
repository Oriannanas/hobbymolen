<?php
/**
 * Created by PhpStorm.
 * User: marijn
 * Date: 11-Nov-17
 * Time: 18:05
 */

namespace MMDev\ImportExport\Model\Import\Source;


class Csv extends \Magento\ImportExport\Model\Import\Source\Csv {
  private $_currRow = 0;

  public function __construct($file, \Magento\Framework\Filesystem\Directory\Read $directory, $delimiter = ',', $enclosure = '"') {
    parent::__construct($file, $directory, $delimiter, $enclosure);
  }

  protected function _getNextRow() {
    if($this->_currRow === 0){

    }
    $lRow = parent::_getNextRow();
    $this->_currRow++;
    return $lRow;
  }

  public function rewind() {
    parent::rewind();
    $this->_currRow = 0;
  }

  public function current() {
    $lCurrent = parent::current();
    return $lCurrent;
  }

}