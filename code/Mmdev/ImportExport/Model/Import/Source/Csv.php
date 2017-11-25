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

  public function rewind() {
    parent::rewind();
    $this->_currRow = 0;
  }

  public function current() {
    $lRowData = parent::current();
    $lRowData = $this->mapData($lRowData);

    return $lRowData;
  }

  private function mapData($pRowData) {
    if (!isset($pRowData['product_type'])) {
      $pRowData['product_type'] = 'simple';
    }

    if (!isset($pRowData['attribute_set_id'])) {
      $pRowData['_attribute_set'] = 'Hobbymolen';
    }

    if (!isset($pRowData['url_key']) && isset($pRowData['sku'])) {
      $pRowData['url_key'] = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $pRowData['sku'])));
    }

    if (!isset($pRowData['image']) && isset($pRowData['artikellev_nummerts20'])) {
//      $pRowData['image'] = '/' . preg_replace('/[^a-zA-Z0-9-]/', '', $pRowData['artikellev_nummerts20']) . '.png';
    }

    if (!isset($pRowData['additional_attributes']) && isset($pRowData['artikelreintnumrelatienaamts30'])) {
      $pRowData['additional_attributes'] = 'manufacturer=' . $pRowData['artikelreintnumrelatienaamts30'];
    }

    //categories
    if (empty($pRowData['categories'])) {
      $lCategoryMapping = $this->getCategoryMapping();
      if (!empty($pRowData['artikelomintnumni4'])) ;
      {
        $lCategory = $pRowData['artikelomintnumni4'];
        if (!empty($lCategoryMapping[(string)$lCategory])) {
          $lMain = "Default Category/" . $lCategoryMapping[(string)$lCategory];
        } else {
          $lMain = $lCategory.'-category';
        }
        $lCategories = $lMain;
      }
      $lSubCategoryMapping = $this->getSubCategoryMapping();
      if (!empty($pRowData['artikelsuintnumni4'])) ;
      {
        $lSubCategory = $pRowData['artikelsuintnumni4'];
        if (!empty($lSubCategoryMapping[(string)$lSubCategory])) {
          $lSub = $lSubCategoryMapping[(string)$lSubCategory];
        } else {
          $lSub = $lSubCategory.'-subcategory';
        }
        $lCategories .= ',' . $lMain . '/' . $lSub;
      }
      if (!empty(trim($pRowData['artikeldiv_1ts30']))) ;
      {
        $lSubSub     = ucwords(str_replace(' ', '', strtolower($pRowData['artikeldiv_1ts30'])));
        $lCategories .= ',' . $lMain . '/' . $lSub . '/' . $lSubSub;
      }
      error_log($lCategories);
      $pRowData['categories'] = $lCategories;
    }

    return $pRowData;
  }

  protected function getCategoryMapping() {
    return [
      "3"   => "Boeken Enzo",
      "58"  => "Sieraden Maken",
      "109" => "Home Deco",
      "161" => "Basis Materialen",
      "162" => "Papier Technieken",
      "163" => "Textiel Technieken",
    ];
  }

  protected function getSubCategoryMapping() {
    return [

      /**
       * Basis Materialen
       */
      "4"   => "Boetseren",
      "186" => "Chenille & Pompons",
      "175" => "Foam",
      "18"  => "Hout",
      "24"  => "Kinder Hobby",
      "34"  => "Karton",
      "29"  => "Lijm Produkten",
      "87"  => "Metaal & Aluminium",
      "173" => "Watten Vormen",
      "242" => "Raffia",
      "45"  => "Styropor",
      "93"  => "Transparante Vormen",
      "162" => "Veren",
      ""    => "Ogen & Neuzen",

      /**
       * Boeken Enzo
       */
      "273" => "Basis Technieken",
      "109" => "Home Deco",
      "270" => "Papier Technieken",
      "271" => "Sieraden Maken",
      "272" => "Textiel Technieken",
      "47"  => "Tekenen & Schilderen",

      /**
       * Sieraden Maken
       */
      "165" => "Bedels & Hangers",
      //""  => "Cabochons",
      "14"  => "Gereedschap",
      "174" => "Ketting",
      "269" => "Kralen per stuk",
      "27"  => "Kralen verpakt",
      "152" => "Pakketten of sets",
      "58"  => "Onderdelen",
      "96"  => "Rijgbenodigdheden",
      "184" => "Swarovski",
      "267" => "Veters",

      /**
       * Home Deco
       */
      "9"   => "Decoratie Materiaal",
      "99"  => "Decoupage",
      //"14" => "Gereedschap", duplicate
      //""=> "Onbeschilderde dragers",
      "138" => "Sjablonen",
      "94"  => "Verf",

      /**
       * Papier Technieken
       */
      "264" => "3D",
      "263" => "Borduren op Papier",
      //"9" => "Decoratie Materiaal", duplicate
      //"14" => "Gereedschap", duplicate
      "262" => "Kaart Pakketten",
      //"34"  => "Karton", duplicate
      //"24"  => "Kinderhobby", duplicate
      "268" => "Kleurmiddelen",
      //"29"  => "Lijm Produkten", duplicate
      "171" => "Machines",
      "124" => "Origami",
      "167" => "Papier",
      "39"  => "Ponsen",
      "92"  => "Stansen & Mallen",
      "42"  => "Stempelen",
      "43"  => "Stickervellen",

      /**
       * Textiel Technieken
       */
      "85"  => "Borduurgaren",
      //"" => "Decoratie",
      "6"   => "Brei- & Haakgaren",
      "10"  => "Diversen",
      "142" => "Garen",
      //"" => "Gereedschappen textiel",
      //"" => "Haak Katoen",
      //"" => "Hot-Fix",
      "44"  => "Lapjes Stof",
      //"" => "Vilt Pakketten",
      "123" => "Vilt Syntetisch",
      "121" => "Vilt Wol",
    ];
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

  private function mapColumns($pColumnNames) {
    $lMapping     = $this->getColumnMapping();
    $pColumnNames = array_map(function ($pColumnName) use ($lMapping) {
      if (isset($lMapping[$pColumnName])) {
        return $lMapping[$pColumnName];
      }

      return $this->cleanColumnName($pColumnName); //replace odd characters to not trigger errors
    }, $pColumnNames);

    return $pColumnNames;
  }

  protected function getColumnMapping() {
    return [
      'ARTIKEL.CODE[TS6]'     => 'sku',
      'ARTIKEL.KRT_OMS[TS30]' => 'name',
      'ARTIKEL.LNG_OMS[TS80]' => 'short_description',//maybe description?
      'ARTIKEL.VRK_PR_I[NR8]' => 'price',
      'ARTIKEL.ACT_VRD[NR8]'  => 'qty',
    ];
  }

  private function cleanColumnName($pColumnName) {
    return preg_replace('/[^a-z0-9_]/', '', strtolower($pColumnName));
  }

}