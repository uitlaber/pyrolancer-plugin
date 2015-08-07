<?php namespace Ahoy\Pyrolancer\Models;

use Mail;
use Model;
use October\Rain\Support\Str;
use Backend\Models\UserGroup;
use Ahoy\Pyrolancer\Models\Settings as SettingsModel;

/**
 * A log of project moderation events
 */
class ProjectStatusLog extends Model
{
    public $table = 'ahoy_pyrolancer_project_status_log';

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['type', 'message_md', 'message_html'];

    public $belongsTo = [
        'user' => ['RainLab\User\Models\User'],
    ];

    /**
     * @var array Relations
     */
    public $morphTo = [
        'project' => []
    ];

    public static function createForProject($project)
    {
        $obj = new self;
        $obj->project_id = $project->id;
        return $obj;
    }

    public function getMessagePreview($length = 256)
    {
        if (strlen($this->message_preview))
            return $this->message_preview;

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
        if (!$status = Attribute::forType(Attribute::PROJECT_STATUS)->whereCode($code)->first())
            return false;

        // Same status
        if ($status->id == $project->status_id)
            return;

        $oldStatus = $project->status;

        $log = self::createForProject($project);
        $log->old_status_id = $project->status_id;
        $log->new_status_id = $status->id;

        if ($oldStatus) {

            if ($oldStatus->code == Project::STATUS_DRAFT && $status->code == Project::STATUS_PENDING) {
                self::processApprovalRequest($project, $log, $data);
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_ACTIVE) {
                self::processProjectApproved($project, $log, $data);
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_REJECTED) {
                self::processProjectRejected($project, $log, $data);
            }

            if ($status->code == Project::STATUS_SUSPENDED) {
                self::processProjectSuspended($project, $log, $data);
            }

        }

        $log->save();

        $project->status_log()->add($log);
        $project->status = $status;
        $project->save();
    }

    public static function processApprovalRequest($project, $log, $data = null)
    {
        $adminGroup = SettingsModel::get('notify_admin_group');
        if (!$group = UserGroup::whereCode($adminGroup)->first())
            return;

        $params = [
            'project' => $project,
            'user' => $project->user,
        ];

        $admins = $group->users()->lists('first_name', 'email');
        Mail::sendTo($admins, 'ahoy.pyrolancer::mail.project-approval-request', $params);
    }

    public static function processProjectApproved($project, $log, $data = null)
    {
        $params = [
            'project' => $project,
            'user' => $project->user,
        ];

        Mail::sendTo($project->user, 'ahoy.pyrolancer::mail.client-project-approved', $params);
    }

    public static function processProjectRejected($project, $log, $data = null)
    {
        $log->message_md = array_get($data, 'reason');
        $log->message_html = Markdown::parse(trim($this->message_md));

        $params = [
            'project' => $project,
            'user' => $project->user,
            'reason' => $log->message_html,
        ];

        Mail::sendTo($project->user, 'ahoy.pyrolancer::mail.client-project-rejected', $params);
    }

    public static function processProjectSuspended($project, $log, $data = null)
    {

    }

}