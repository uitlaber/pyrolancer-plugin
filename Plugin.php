<?php namespace Ahoy\Pyrolancer;

use Backend;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['RainLab.User', 'RainLab.Location'];

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
        UserModel::extend(function($model) {
            $model->hasOne['worker'] = ['Ahoy\Pyrolancer\Models\Worker'];
            $model->hasOne['client'] = ['Ahoy\Pyrolancer\Models\Client'];

            $model->addFillable(['phone', 'mobile', 'street_addr', 'city', 'zip']);

            $model->implement[] = 'RainLab.Location.Behaviors.LocationModel';
        });

        UsersController::extendFormFields(function($widget){
            $widget->addTabFields([
                'phone' => ['label' => 'Phone', 'tab' => 'Profile', 'span' => 'left'],
                'mobile' => ['label' => 'Mobile', 'tab' => 'Profile', 'span' => 'right'],
                'street_addr' => ['label' => 'Street Address', 'tab' => 'Profile'],
                'city' => ['label' => 'City', 'tab' => 'Profile', 'span' => 'left'],
                'zip' => ['label' => 'Zip', 'tab' => 'Profile', 'span' => 'right'],
                'country' => ['label' => 'country', 'type' => 'dropdown', 'tab' => 'Profile', 'span' => 'left'],
                'state' => ['label' => 'state', 'type' => 'dropdown', 'tab' => 'Profile', 'span' => 'right', 'dependsOn' => 'country']
            ]);
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
           '\Ahoy\Pyrolancer\Components\Activity'          => 'activity',
           '\Ahoy\Pyrolancer\Components\Account'           => 'account',
           '\Ahoy\Pyrolancer\Components\Dashboard'         => 'dashboard',
           '\Ahoy\Pyrolancer\Components\Projects'          => 'projects',
           '\Ahoy\Pyrolancer\Components\Project'           => 'project',
           '\Ahoy\Pyrolancer\Components\ProjectSubmit'     => 'projectSubmit',
           '\Ahoy\Pyrolancer\Components\Worker'            => 'worker',
           '\Ahoy\Pyrolancer\Components\WorkerManage'      => 'workerManage',
           '\Ahoy\Pyrolancer\Components\WorkerRegister'    => 'workerRegister',
           '\Ahoy\Pyrolancer\Components\WorkerPortfolio'   => 'workerPortfolio',
           '\Ahoy\Pyrolancer\Components\WorkerTestimonial' => 'workerTestimonial',
           '\Ahoy\Pyrolancer\Components\ClientProjects'    => 'clientProjects',
           '\Ahoy\Pyrolancer\Components\Profile'           => 'profile',
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
