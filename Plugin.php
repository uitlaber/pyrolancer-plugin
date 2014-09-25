<?php namespace Responsiv\Pyrolancer;

use Backend;
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


    public function registerNavigation()
    {
        return [
            'pyrolancer' => [
                'label'       => 'Freelance',
                'url'         => Backend::url('responsiv/pyrolancer/freelancers'),
                'icon'        => 'icon-briefcase',
                'permissions' => ['blog.*'],
                'order'       => 500,

                'sideMenu' => [
                    'freelancers' => [
                        'label'       => 'Freelancers',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('responsiv/pyrolancer/freelancers'),
                        'permissions' => ['pyrolancer.access_freelancers'],
                    ],
                    'projects' => [
                        'label'       => 'Projects',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('responsiv/pyrolancer/projects'),
                        'permissions' => ['pyrolancer.access_projects'],
                    ],
                    'categories' => [
                        'label'       => 'Categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('responsiv/pyrolancer/categories'),
                        'permissions' => ['pyrolancer.access_categories'],
                    ],
                    'skills' => [
                        'label'       => 'Skills',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('responsiv/pyrolancer/skills'),
                        'permissions' => ['pyrolancer.access_skills'],
                    ],
                ]

            ]
        ];
    }

    public function registerComponents()
    {
        return [
           '\Responsiv\Pyrolancer\Components\PostProject'     => 'postProject',
        ];
    }

    public function registerFormWidgets()
    {
        return [
            'Responsiv\Pyrolancer\FormWidgets\GooglePlace' => [
                'label' => 'Google Address',
                'alias' => 'googleaddress'
            ],
        ];
    }


}
