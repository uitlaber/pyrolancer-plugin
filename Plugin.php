<?php namespace Responsiv\Pyrolancer;

use System\Classes\PluginBase;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Pyrolancer',
            'description' => 'No description provided yet...',
            'author'      => 'Responsiv',
            'icon'        => 'icon-leaf'
        ];
    }

}
