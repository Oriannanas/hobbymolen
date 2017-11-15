<?php
/**
 * Created by PhpStorm.
 * User: marijn
 * Date: 11-Nov-17
 * Time: 18:05
 */

namespace Mmdev\ImportExport\Model\Import\Source;

class Csv extends \Magento\ImportExport\Model\Import\Source\Csv {
  private $_currRow = 0;

  protected function getColumnMapping() {
    return [
      'ARTIKEL.CODE[TS6]'     => 'sku',
      'ARTIKEL.KRT_OMS[TS30]' => 'name',
      'ARTIKEL.LNG_OMS[TS80]' => 'short_description',//maybe description?
      'ARTIKEL.VRK_PR_I[NR8]' => 'price',
      'ARTIKEL.ACT_VRD[NR8]'  => 'qty',
    ];
  }
  protected function getCategoryMapping() {
    return [
      "",
    ];
  }

  private function mapColumns($pColumnNames) {
    $lMapping = $this->getColumnMapping();
    $pColumnNames = array_map(function ($pColumnName) use ($lMapping) {
      if (isset($lMapping[$pColumnName])) {
        return $lMapping[$pColumnName];
      }

      return $this->cleanColumnName($pColumnName); //replace odd characters to not trigger errors
    }, $pColumnNames);

    return $pColumnNames;
  }

  private function cleanColumnName($pColumnName){
    return preg_replace('/[^a-z0-9_]/', '', strtolower($pColumnName));
  }

  private function mapData($pRowData) {
    if(!isset($pRowData['product_type'])){
      $pRowData['product_type'] = 'simple';
    }

    if(!isset($pRowData['attribute_set_id'])){
      $pRowData['_attribute_set'] = 'Hobbymolen';
    }

    if(!isset($pRowData['image']) && isset($pRowData['atrikellev_nummerts20'])){
      $pRowData['image'] = '/'.$pRowData['atrikellev_nummerts20'].'.jpg';
    }

    if(!isset($pRowData['additional_attributes']) && isset($pRowData['artikelreintnumrelatienaamts30'])){
      $pRowData['additional_attributes'] = 'manufacturer='.$pRowData['artikelreintnumrelatienaamts30'];
    }

    //categories
    if(!isset($pRowData['categories'])){
      if(isset($pRowData['artikelomintnumomzgroepcodets6']));{
        $lMain = "Default Category/".ucwords(str_replace(' ', '', strtolower($pRowData['artikelomintnumomzgroepcodets6'])));
        $lCategories = $lMain;
      }
      if(isset($pRowData[$this->cleanColumnName('artikelsuintnumsubgroepcodets6')]));{
        $lSub = ucwords(str_replace(' ', '', strtolower($pRowData['artikelsuintnumsubgroepcodets6'])));
        $lCategories .= ','.$lMain.'/'.$lSub;
      }
      if(isset($pRowData[$this->cleanColumnName('artikeldiv_1ts30')]));{
        $lSubSub = ucwords(str_replace(' ', '', strtolower($pRowData['artikeldiv_1ts30'])));
        $lCategories .= ','.$lMain.'/'.$lSub.'/'.$lSubSub;
      }
      $pRowData['categories'] = $lCategories;
    }

    return $pRowData;
  }



  protected function _getNextRow() {
    $lRow = parent::_getNextRow();
    if ($this->_currRow === 0) {
      //current rowData is used for column mapping, interfere here to allow our favorite webshop's wonky export to be used with magento's import
      $lRow = $this->mapColumns($lRow);
    }
    $this->_currRow++;

    return $lRow;
  }

  public function rewind() {
    parent::rewind();
    $this->_currRow = 0;
  }

  public function current() {
    $lRowData = parent::current();
    $lRowData = $this->mapData($lRowData);
    return $lRowData;
  }

}