<?php namespace Ahoy\Pyrolancer\Models;

use Model;
use October\Rain\Support\Str;

/**
 * A log of project moderation events
 */
class ProjectStatusLog extends Model
{
    const TYPE_SUBMITTED_APPROVAL = 'submitted-approval';
    const TYPE_RESUBMITTED_APPROVAL = 'resubmitted-approval';
    const TYPE_REJECTED = 'rejected';
    const TYPE_APPROVED = 'approved';
    const TYPE_SUSPENDED = 'suspended';

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

    public function getTypeName()
    {
        $statusNames = [
            self::TYPE_SUBMITTED_APPROVAL => 'Submitted for approval',
            self::TYPE_RESUBMITTED_APPROVAL => 'Resubmitted for approval',
            self::TYPE_REJECTED => 'Rejected',
            self::TYPE_APPROVED => 'Approved',
            self::TYPE_SUSPENDED => 'Suspended'
        ];

        if (isset($statusNames[$this->type]))
            return $statusNames[$this->type];

        return 'Unknown event';
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
        if (!$status = ProjectOption::forType(ProjectOption::PROJECT_STATUS)->whereCode($code)->first())
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
                self::processApprovalRequest($project);
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_ACTIVE) {
                self::processProjectApproved($project);
            }

            if ($oldStatus->code == Project::STATUS_PENDING && $status->code == Project::STATUS_REJECTED) {
                self::processProjectRejected($project);
            }

            if ($status->code == Project::STATUS_SUSPENDED) {
                self::processProjectSuspended($project);
            }

        }

        $log->save();

        $project->status_log()->add($log);
        $project->status = $status;
        $project->save();
    }

    public static function processApprovalRequest($project)
    {

    }

    public static function processProjectApproved($project)
    {

    }

    public static function processProjectRejected($project)
    {

    }

    public static function processProjectSuspended($project)
    {

    }

}