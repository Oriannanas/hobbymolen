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

    if (!isset($pRowData['product_websites'])) {
      $pRowData['_product_websites'] = 'base';
    }

    if (!isset($pRowData['visibility'])) {
      $pRowData['visibility'] = 'Catalogus, Zoeken';
    }

    if (!isset($pRowData['product_online'])) {
      $pRowData['product_online'] = '1';
    }
    if (!isset($pRowData['weight'])) {
      $pRowData['weight'] = '1';
    }

    if (!isset($pRowData['url_key']) && isset($pRowData['sku'])) {
      $pRowData['url_key'] = preg_replace('/[^a-z0-9.- ]/', '', strtolower(str_replace(['@'], '-', $pRowData['sku'])));
    }

    $importImageFolder = realpath(dirname(__FILE__).'/../../../../../../..').'/'.'pub/media/import';

    if (!isset($pRowData['image']) && isset($pRowData['sku'])) {
      $lImageName = preg_replace('/[^a-zA-Z0-9._-]/', '', $pRowData['sku']);
      $lImageNameLarge = $lImageName . '_1200x1200.png';
      if(file_exists($importImageFolder. '/' .$lImageNameLarge)){
        $pRowData['image'] = '/' . $lImageNameLarge;
        $pRowData['base_image'] = '/' . $lImageNameLarge;
      }
      $lImageNameMedium = $lImageName . '_324x324.png';
      if(file_exists($importImageFolder.'/' .$lImageNameMedium)){
        $pRowData['small_image'] = '/' . $lImageNameMedium;
      }
      $lImageNameSmall = $lImageName . '_135x135.png';
      if(file_exists($importImageFolder.'/' . $lImageNameSmall)){
        $pRowData['thumbnail_image'] = '/' . $lImageNameSmall;
      }
    }

    if (!isset($pRowData['additional_attributes'])) {
      $pRowData['additional_attributes'] = [];
      if (isset($pRowData['artikelreintnumrelatienaamts30'])) {
        $pRowData['relatie']               = $pRowData['artikelreintnumrelatienaamts30'];
        $pRowData['additional_attributes'][] = 'relatie=' . $pRowData['artikelreintnumrelatienaamts30'];
      }
      if (isset($pRowData['artikelvrk_prnr8'])) {
        $pRowData['price_excl']               = (float)$pRowData['artikelvrk_prnr8'];
        $pRowData['additional_attributes'] = 'price_excl=' . $pRowData['price_excl'];
      }
      if (isset($pRowData['artikeladvvrk_pr_inr8'])) {
        $pRowData['msrp_incl']               = (float)$pRowData['artikeladvvrk_pr_inr8'];
        $pRowData['additional_attributes'] = 'msrp_incl=' . $pRowData['msrp_incl'];
      }
      if (isset($pRowData['artikeladvvrk_pr_nr8'])) {
        $pRowData['msrp_excl']               = (float)$pRowData['artikeladvvrk_pr_nr8'];
        $pRowData['additional_attributes'] = 'msrp_excl=' . $pRowData['msrp_excl'];
      }
      $pRowData['additional_attributes'] = implode(',', $pRowData['additional_attributes']);
    }

    //categories
    if (empty($pRowData['categories'])) {
      $lCategoryMapping = $this->getCategoryMapping();
      if (!empty($pRowData['artikelomintnumni4'])) {
        $lCategory = $pRowData['artikelomintnumni4'];
        if (!empty($lCategoryMapping[(string)$lCategory])) {
          $lCategories = "Default Category";
          $lMain = "Default Category/" . $lCategoryMapping[(string)$lCategory];
        } else {
          $lCategories = "Unsorted Categories";
          $lMain = "Unsorted Categories/" . $lCategory.'-temp';
        }
        $lCategories     .= ','.$lMain;
        $lSubCategoryMapping = $this->getSubCategoryMapping();
        if (!empty($pRowData['artikelsuintnumni4'])) {
          $lSubCategory = $pRowData['artikelsuintnumni4'];
          if (!empty($lSubCategoryMapping[(string)$lSubCategory])) {
            $lSub = $lSubCategoryMapping[(string)$lSubCategory];
          } else {
            $lSub = $lSubCategory.'-temp';
          }
          $lSub = preg_replace('/[^a-zA-Z0-9 -]/', '', $lSub);
          if (!empty($lSub)) {
            error_log($lSub);
            $lCategories .= ',' . $lMain . '/' . $lSub;
          }
          if (!empty($pRowData['artikeldiv_1ts30'])) ;
          {
            $lSubSub = ucfirst(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '', strtolower($pRowData['artikeldiv_1ts30']))));
            if (!empty($lSubSub)) {
              error_log($lSubSub);
              $lCategories .= ',' . $lMain . '/' . $lSub . '/' . $lSubSub;
            }
          }
        }
        error_log($lCategories);
        $pRowData['categories'] = $lCategories;
      }
    }
    return $pRowData;
  }

  protected function getCategoryMapping() {
    return [
      "8"   => "Boeken Enzo",
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
      "9"   => "Decoratie Materiaal",
      "175" => "Foam",
      "18"  => "Hout",
      "14"  => "Gereedschap",
      "34"  => "Karton",
      "24"  => "Kinder Hobby",
      "29"  => "Lijm Produkten",
      "87"  => "Metaal & Aluminium",
      "173" => "Wattenvormen",
      "242" => "Raffia",
      "45"  => "Styropor",
      "93"  => "Transparante Vormen",
      "162" => "Veren",
//      ""    => "Ogen & Neuzen",

      /**
       * Boeken Enzo
       */
      "273" => "Basis Technieken",
      "109" => "Home Deco",
      "270" => "Papier Technieken",
      "271" => "Sieraden Maken",
      "272" => "Textiel Technieken",
      "47"  => "Tekenen & Schilderen",
      "256" => "Tijdschriften",
      /**
       * Sieraden Maken
       */
      "165" => "Bedels & Hangers",
      "275"  => "Cabochons",
//      "14"  => "Gereedschap",
      "174" => "Ketting",
      "269" => "Kralen per stuk",
      "27"  => "Kralen verpakt",
      "58"  => "Onderdelen",
      "152" => "Pakketten of sets",
      "96"  => "Rijgbenodigdheden",
      "184" => "Swarovski",
      "267" => "Veters",

      /**
       * Home Deco
       */
//      "9"   => "Decoratie Materiaal",
      "99"  => "Decoupage",
      //"14" => "Gereedschap", duplicate
      "274"=> "Onbeschilderde dragers",
      "138" => "Sjablonen",
      "111" => "Textuek Verharders",
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
      "63" => "Scrapbook Materialen",
      "92"  => "Stansen & Mallen",
      "42"  => "Stempelen",
      "43"  => "Stickervellen",

      /**
       * Textiel Technieken
       */
      "85"  => "Borduurgaren",
      "6"   => "Brei- & Haakgaren",
//      "9" => "Decoratie materiaal",
      "10"  => "Diversen",
      "142" => "Garen",
      "88" => "Gereedschappen textiel",
      //"" => "Haak Katoen",
      "114" => "Hot-Fix",
      "44"  => "Lapjes Stof",
      "150" => "Vilt Pakketten",
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
    error_log($this->_currRow);

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
      'ARTIKEL.LEV_NUMMER[TS20]'     => 'sku',
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