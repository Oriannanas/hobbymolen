<?php
/**
 * @copyright: Copyright © 2015 Mmdev Studio. All rights reserved.
 * @author: Mmdev Studio <fbeardev@gmail.com>
 */

namespace Mmdev\ImportExport\Model\Source;

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