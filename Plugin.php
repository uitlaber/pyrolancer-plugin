<?php namespace Ahoy\Pyrolancer;

use Event;
use Backend;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use Ahoy\Pyrolancer\Models\UserEventLog;

/**
 * Pyrolancer Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['RainLab.User', 'RainLab.Location', 'RainLab.UserPlus'];

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

    public function register()
    {
        $this->registerConsoleCommand('jobs.run', 'Ahoy\Pyrolancer\Console\JobsRun');
    }

    public function boot()
    {
        UserModel::extend(function($model) {
            $model->hasOne['worker'] = ['Ahoy\Pyrolancer\Models\Worker'];
            $model->hasOne['client'] = ['Ahoy\Pyrolancer\Models\Client'];

            $model->bindEvent('model.afterCreate', function() use ($model) {
                UserEventLog::add(UserEventLog::TYPE_USER_CREATED, [
                    'user' => $model,
                    'createdAt' => $model->created_at
                ]);
            });
        });

        Event::listen('backend.form.extendFields', function ($widget) {
            if (
                !$widget->getController() instanceof \RainLab\Pages\Controllers\Index ||
                !$widget->model instanceof \RainLab\Pages\Classes\MenuItem
            ) {
                return;
            }

            $widget->addTabFields([
                'viewBag[visibleTo]' => [
                    'tab' => 'User group',
                    'commentAbove' => 'Make this menu item visible only to the following groups.',
                    'type' => 'radio',
                    'default' => 'all',
                    'options' => [
                        'all' => 'Everyone',
                        'clients' => 'Clients',
                        'workers' => 'Workers',
                        'users' => 'Users',
                    ]
                ]
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
                'order'       => 10,

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
           '\Ahoy\Pyrolancer\Components\Jobs'              => 'jobs',
           '\Ahoy\Pyrolancer\Components\AttributeValues'   => 'attributeValues',
           '\Ahoy\Pyrolancer\Components\Activity'          => 'activity',
           '\Ahoy\Pyrolancer\Components\Account'           => 'account',
           '\Ahoy\Pyrolancer\Components\Dashboard'         => 'dashboard',
           '\Ahoy\Pyrolancer\Components\Directory'         => 'directory',
           '\Ahoy\Pyrolancer\Components\Project'           => 'project',
           '\Ahoy\Pyrolancer\Components\ProjectSubmit'     => 'projectSubmit',
           '\Ahoy\Pyrolancer\Components\ProjectCollab'     => 'projectCollab',
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
            'ahoy.pyrolancer::mail.project-reapproval-request' => 'Sent to managers when a previously rejected project has been resubmitted for approval.',
            'ahoy.pyrolancer::mail.client-project-approved' => 'Sent to the client when their project is approved.',
            'ahoy.pyrolancer::mail.client-project-rejected' => 'Sent to the client when their project is rejected.',
            'ahoy.pyrolancer::mail.worker-testimonial-request' => "Sent to the worker's previous client or workplace, requesting they submit a testimonial about the worker.",
            'ahoy.pyrolancer::mail.worker-testimonial-complete' => "Sent to the worker when a previous client has left a testimonial about them.",
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Freelance settings',
                'description' => 'Manage freelance features and settings.',
                'category'    => 'Freelance',
                'icon'        => 'icon-briefcase',
                'class'       => 'Ahoy\Pyrolancer\Models\Settings',
                'order'       => 500,
                'keywords'    => 'project worker client'
            ]
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function(){
            SupportWorker::instance()->process();
        })->everyFiveMinutes();
    }
}
