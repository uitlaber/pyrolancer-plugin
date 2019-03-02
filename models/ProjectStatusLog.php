<?php namespace Responsiv\Pyrolancer\Models;

use Mail;
use Model;
use Queue;
use Markdown;
use Cms\Classes\Theme;
use October\Rain\Support\Str;
use Backend\Models\UserGroup;
use Responsiv\Pyrolancer\Models\Settings as SettingsModel;
use Responsiv\Pyrolancer\Classes\Usher;

/**
 * A log of project moderation events
 */
class ProjectStatusLog extends Model
{
    public $table = 'responsiv_pyrolancer_project_status_log';

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['type', 'message', 'message_html'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
        'project' => 'Responsiv\Pyrolancer\Models\Project',
    ];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['data'];

    public $notifyUserTemplate = null;
    public $notifyStaffTemplate = null;

    public static function createForProject($project)
    {
        $obj = new self;
        $obj->project = $project;
        return $obj;
    }

    public function afterCreate()
    {
        if ($this->notifyUserTemplate !== null) {
            $this->notifyUser();
        }

        if ($this->notifyStaffTemplate !== null) {
            $this->notifyStaff();
        }
    }

    public function getMessagePreview($length = 256)
    {
        if (strlen($this->message_preview)) {
            return $this->message_preview;
        }

        $this->timestamps = false;
        $this->message_preview = Str::limitHtml($this->message_html, $length);
        $this->save();

        return $this->message_preview;
    }

    public function isPreviewTruncated()
    {
        return mb_substr(strip_tags($this->getMessagePreview()), -3) == '...';
    }

    //
    // Workflow
    //

    public static function updateProjectStatus($project, $code, $data = null)
    {
        if (!$status = Attribute::applyType(Attribute::PROJECT_STATUS)->whereCode($code)->first())
            return false;

        // Same status
        if ($status->id == $project->status_id) {
            return;
        }

        // Process message markdown
        if (is_array($data) && isset($data['message'])) {
            $data['message_html'] = Markdown::parse(array_get($data, 'message'));
        }

        $oldStatus = $project->status;

        $log = self::createForProject($project);
        $log->old_status_id = $project->status_id;
        $log->new_status_id = $status->id;

        if ($oldStatus) {

            if ($oldStatus->code == Project::STATUS_DRAFT && $status->code == Project::STATUS_PENDING) {
                $log->notifyStaffTemplate = 'responsiv.pyrolancer::mail.project-approval-request';
            }

            if ($oldStatus->code == Project::STATUS_REJECTED && $status->code == Project::STATUS_PENDING) {
                $log->notifyStaffTemplate = 'responsiv.pyrolancer::mail.project-reapproval-request';
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_ACTIVE) {
                $log->notifyUserTemplate = 'responsiv.pyrolancer::mail.client-project-approved';

                UserEventLog::add(UserEventLog::TYPE_PROJECT_CREATED, [
                    'user' => $project->user,
                    'related' => $project,
                ]);

                if ($project->is_urgent) {
                    Usher::queueUrgentProject($project);
                }

                if (!$project->is_remote) {
                    Usher::queueProjectVicinity($project);
                }
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_REJECTED) {
                $log->notifyUserTemplate = 'responsiv.pyrolancer::mail.client-project-rejected';
            }

            if ($oldStatus->code == Project::STATUS_WAIT && $status->code == Project::STATUS_DECLINED) {
                $log->notifyUserTemplate = 'responsiv.pyrolancer::mail.client-bid-declined';
            }

            if ($oldStatus->code == Project::STATUS_WAIT && $status->code == Project::STATUS_DEVELOPMENT) {
                $log->notifyUserTemplate = 'responsiv.pyrolancer::mail.client-bid-confirmed';
            }

            if ($oldStatus->code == Project::STATUS_ACTIVE && $status->code == Project::STATUS_EXPIRED) {
                $log->notifyUserTemplate = 'responsiv.pyrolancer::mail.client-project-expired';
            }

            if ($status->code == Project::STATUS_SUSPENDED) {
                // @todo
            }

        }

        // @todo This could be deferred to the Queue
        if ($project->client) {
            $project->client->setRatingStats();
            $project->client->save();
        }

        $log->data = $data;
        $log->save();

        $project->status_log()->add($log);
        $project->status = $status;
        $project->save();
    }

    protected function notifyUser($template = null)
    {
        $template = $template ?: $this->notifyUserTemplate;

        $params = [
            'site_name' => Theme::getActiveTheme()->site_name,
            'project' => $this->project,
            'user' => $this->project->user,
            'reason' => array_get($this->data, 'message_html'),
        ];

        Mail::sendTo($this->project->user, $template, $params);
    }

    protected function notifyStaff($template = null)
    {
        $template = $template ?: $this->notifyStaffTemplate;

        $adminGroup = SettingsModel::get('notify_admin_group');
        if (!$group = UserGroup::whereCode($adminGroup)->first()) {
            return;
        }

        $params = [
            'site_name' => Theme::getActiveTheme()->site_name,
            'project' => $this->project,
            'user' => $this->project->user,
            'reason' => array_get($this->data, 'message_html'),
        ];

        $admins = $group->users()->lists('first_name', 'email');
        Mail::sendTo($admins, $template, $params);
    }

}
