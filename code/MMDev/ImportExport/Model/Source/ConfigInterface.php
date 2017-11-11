<?php
/**
 * @copyright: Copyright © 2015 MMDev Studio. All rights reserved.
 * @author: MMDev Studio <fbeardev@gmail.com>
 */

namespace MMDev\ImportExport\Model\Source;

interface ConfigInterface
{
    /**
     * Get configuration of source type by name
     *
     * @param string $name
     * @return array
     */
    public function getType($name);
}