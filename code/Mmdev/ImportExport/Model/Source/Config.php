<?php
/**
 * @copyright: Copyright © 2015 Mmdev Studio. All rights reserved.
 * @author: Mmdev Studio <fbeardev@gmail.com>
 */

namespace Mmdev\ImportExport\Model\Source;

/**
 * Class Config
 * @package Mmdev\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \Mmdev\ImportExport\Model\Source\ConfigInterface
{

    /**
     * @param Config\Reader                            $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string                                   $cacheId
     */
    public function __construct(
        \Mmdev\ImportExport\Model\Source\Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        $cacheId = 'mmdev_importexport_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get system configuration of source type by name
     *
     * @param string $name
     * @return array|mixed|null
     */
    public function getType($name)
    {
        return $this->get('type/' . $name, []);
    }
}