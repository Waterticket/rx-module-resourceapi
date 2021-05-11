<?php

/**
 * Resource Api
 * 
 * Copyright (c) Waterticket
 * 
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ResourceapiModel extends Resourceapi
{
	public function increaseDownloadCount($module_srl, $package_srl)
    {
        $args = new stdClass();
        $args->package_srl = $package_srl;
        $args->module_srl = $module_srl;

        $output = executeQuery('resource.updateItemDownloadedCount', $args);
        $output = executeQuery('resource.updatePackageDownloadedCount', $args);
    }
}
