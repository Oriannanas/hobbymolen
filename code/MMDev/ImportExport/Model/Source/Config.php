<?php
/**
 * @copyright: Copyright © 2015 MMDev Studio. All rights reserved.
 * @author: MMDev Studio <fbeardev@gmail.com>
 */

namespace MMDev\ImportExport\Model\Source;

/**
 * Class Config
 * @package MMDev\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \MMDev\ImportExport\Model\Source\ConfigInterface
{

    /**
     * @param Config\Reader                            $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string                                   $cacheId
     */
    public function __construct(
        \MMDev\ImportExport\Model\Source\Config\Reader $reader,
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