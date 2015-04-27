<?php namespace Ahoy\Pyrolancer;

use Backend;
use System\Classes\PluginBase;
use RainLab\User\Models\User;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['RainLab.User'];

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
            'author'      => 'Scripts Ahoy',
            'icon'        => 'icon-fire'
        ];
    }

    public function boot()
    {
        User::extend(function($model) {
            $model->hasOne['worker'] = ['Ahoy\Pyrolancer\Models\Worker'];
        });
    }

    public function registerNavigation()
    {
        return [
            'pyrolancer' => [
                'label'       => 'Freelance',
                'url'         => Backend::url('ahoy/pyrolancer/projects'),
                'icon'        => 'icon-briefcase',
                'permissions' => ['blog.*'],
                'order'       => 500,

                'sideMenu' => [
                    'projects' => [
                        'label'       => 'Projects',
                        'icon'        => 'icon-trophy',
                        'url'         => Backend::url('ahoy/pyrolancer/projects'),
                        'permissions' => ['pyrolancer.access_projects'],
                    ],
                    'skills' => [
                        'label'       => 'Skills',
                        'icon'        => 'icon-graduation-cap',
                        'url'         => Backend::url('ahoy/pyrolancer/skills'),
                        'permissions' => ['pyrolancer.access_skills'],
                    ],
                    'workers' => [
                        'label'       => 'Workers',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('ahoy/pyrolancer/workers'),
                        'permissions' => ['pyrolancer.access_workers'],
                    ],
                ]

            ]
        ];
    }

    public function registerComponents()
    {
        return [
           '\Ahoy\Pyrolancer\Components\AttributeValues'   => 'attributeValues',
           '\Ahoy\Pyrolancer\Components\Dashboard'         => 'dashboard',
           '\Ahoy\Pyrolancer\Components\Projects'          => 'projects',
           '\Ahoy\Pyrolancer\Components\Project'           => 'project',
           '\Ahoy\Pyrolancer\Components\ProjectSubmit'     => 'projectSubmit',
           '\Ahoy\Pyrolancer\Components\Worker'            => 'worker',
           '\Ahoy\Pyrolancer\Components\WorkerManage'      => 'workerManage',
           '\Ahoy\Pyrolancer\Components\WorkerRegister'    => 'workerRegister',
           '\Ahoy\Pyrolancer\Components\WorkerTestimonial' => 'workerTestimonial',
           '\Ahoy\Pyrolancer\Components\ClientProjects'    => 'clientProjects',
           '\Ahoy\Pyrolancer\Components\PortfolioManage'   => 'portfolioManage',
        ];
    }


    public function registerMailTemplates()
    {
        return [
            'ahoy.pyrolancer::mail.project-approval-request' => 'Sent to managers when a new project needs approval.',
            'ahoy.pyrolancer::mail.client-project-approved' => 'Sent to the client when their project is approved.',
            'ahoy.pyrolancer::mail.client-project-rejected' => 'Sent to the client when their project is rejected.',
        ];
    }
}
