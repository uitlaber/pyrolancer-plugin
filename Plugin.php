<?php namespace Ahoy\Pyrolancer;

use View;
use Event;
use Backend;
use Cms\Classes\Theme;
use System\Classes\PluginBase;
use RainLab\User\Models\User as UserModel;
use RainLab\Location\Models\State as StateModel;
use RainLab\Location\Models\Country as CountryModel;
use Ahoy\Pyrolancer\Models\UserEventLog;
use Ahoy\Pyrolancer\Classes\Worker as JobWorker;
use Exception;

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
            'author'      => 'Scripts Ahoy!',
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
            $model->hasOne['worker'] = ['Ahoy\Pyrolancer\Models\Worker', 'delete' => true, 'softDelete' => true];
            $model->hasOne['client'] = ['Ahoy\Pyrolancer\Models\Client', 'delete' => true, 'softDelete' => true];
            $model->hasOne['event_log'] = ['Ahoy\Pyrolancer\Models\UserEventLog', 'delete' => true, 'softDelete' => true];

            $model->bindEvent('model.afterCreate', function() use ($model) {
                UserEventLog::add(UserEventLog::TYPE_USER_CREATED, [
                    'user' => $model,
                    'createdAt' => $model->created_at
                ]);
            });
        });

        StateModel::extend(function($model) {
            $model->hasMany['vicinities'] = ['Ahoy\Pyrolancer\Models\Vicinity'];
        });

        CountryModel::extend(function($model) {
            $model->hasMany['user_count'] = ['RainLab\User\Models\User', 'count' => true];
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
                    'type' => 'dropdown',
                    'default' => 'all',
                    'options' => [
                        'all' => 'Everyone',
                        'guests' => 'Guests',
                        'users' => 'Users',
                        'clients' => 'Clients',
                        'workers' => 'Workers',
                        'hybrid' => 'Hybrid',
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
                'iconSvg'     => 'plugins/ahoy/pyrolancer/assets/images/briefcase-icon.svg',
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
           '\Ahoy\Pyrolancer\Components\Collab'            => 'collab',
           '\Ahoy\Pyrolancer\Components\CollabUpdate'      => 'collabUpdate',
           '\Ahoy\Pyrolancer\Components\ContactForm'       => 'contactForm',
           '\Ahoy\Pyrolancer\Components\Dashboard'         => 'dashboard',
           '\Ahoy\Pyrolancer\Components\Directory'         => 'directory',
           '\Ahoy\Pyrolancer\Components\SeoDirectory'      => 'seoDirectory',
           '\Ahoy\Pyrolancer\Components\Favorites'         => 'favorites',
           '\Ahoy\Pyrolancer\Components\Portfolios'        => 'portfolios',
           '\Ahoy\Pyrolancer\Components\Profile'           => 'profile',
           '\Ahoy\Pyrolancer\Components\Project'           => 'project',
           '\Ahoy\Pyrolancer\Components\ProjectSubmit'     => 'projectSubmit',
           '\Ahoy\Pyrolancer\Components\Worker'            => 'worker',
           '\Ahoy\Pyrolancer\Components\WorkerManage'      => 'workerManage',
           '\Ahoy\Pyrolancer\Components\WorkerRegister'    => 'workerRegister',
           '\Ahoy\Pyrolancer\Components\WorkerPortfolio'   => 'workerPortfolio',
           '\Ahoy\Pyrolancer\Components\WorkerTestimonial' => 'workerTestimonial',
           '\Ahoy\Pyrolancer\Components\ClientProjects'    => 'clientProjects',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'ahoy.pyrolancer::mail.project-approval-request' => 'Sent to managers when a new project needs approval.',
            'ahoy.pyrolancer::mail.project-reapproval-request' => 'Sent to managers when a previously rejected project has been resubmitted for approval.',
            'ahoy.pyrolancer::mail.worker-testimonial-request' => "Sent to the worker's previous client or workplace, requesting they submit a testimonial about the worker.",
            'ahoy.pyrolancer::mail.worker-testimonial-complete' => "Sent to the worker when a previous client has left a testimonial about them.",
            'ahoy.pyrolancer::mail.worker-alert' => "Sent to a worker when an urgent project is submitted.",
            'ahoy.pyrolancer::mail.worker-digest' => "Sent to a worker with a compilation of related projects.",
            'ahoy.pyrolancer::mail.worker-bid-accepted' => "Sent to the worker when their bid on a project was accepted by the client.",
            'ahoy.pyrolancer::mail.client-project-approved' => 'Sent to the client when their project is approved.',
            'ahoy.pyrolancer::mail.client-project-rejected' => 'Sent to the client when their project is rejected.',
            'ahoy.pyrolancer::mail.client-bid-confirmed' => "Sent to the client when a project enters development status.",
            'ahoy.pyrolancer::mail.client-bid-declined' => "Sent to the client when the worker's bid was chosen but declined the offer.",
            'ahoy.pyrolancer::mail.client-digest' => "Sent to a client when a new bid or question is placed on their project.",
            'ahoy.pyrolancer::mail.client-project-expired' => "Sent to the client when one of their projects has expired.",
            'ahoy.pyrolancer::mail.collab-message' => "Sent when a user submits a new message to the project collaboration area.",
            'ahoy.pyrolancer::mail.collab-update' => "Sent when a user updates an exisiting collaboration area message with a major update.",
            'ahoy.pyrolancer::mail.collab-terminated' => "Someone terminated the project collaboration.",
            'ahoy.pyrolancer::mail.collab-complete' => "Someone marked the project collaboration as complete.",
            'ahoy.pyrolancer::mail.collab-review' => "Sent to the user when a review is left about them.",
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
            JobWorker::instance()->process();
        })->everyFiveMinutes();
    }
}
