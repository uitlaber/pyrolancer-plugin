<?php namespace Responsiv\Pyrolancer\Models;

use Auth;
use Model;
use Markdown;

/**
 * ProjectMessage Model
 */
class ProjectMessage extends Model
{
    use \Responsiv\Pyrolancer\Traits\ModelUtils;
    use \October\Rain\Database\Traits\SimpleTree;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDeleting;

    const TREE_LABEL = 'created_at';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_project_messages';

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['content'];

    /*
     * Validation
     */
    public $rules = [
        'content' => 'required',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'project' => ['Responsiv\Pyrolancer\Models\Project'],
        'user'    => ['RainLab\User\Models\User'],
        'worker' => ['Responsiv\Pyrolancer\Models\Worker', 'key' => 'user_id', 'otherKey' => 'user_id'],
        'client' => ['Responsiv\Pyrolancer\Models\Client', 'key' => 'user_id', 'otherKey' => 'user_id'],
    ];

    public $attachMany = [
        'attachments' => ['System\Models\File']
    ];

    public function beforeSave()
    {
        if ($this->isDirty('content')) {
            $this->content_html = Markdown::parse(trim($this->content));
        }
    }

    public function afterDelete()
    {
        if ($this->is_public) {
            UserEventLog::remove(UserEventLog::TYPE_PROJECT_MESSAGE, ['related' => $this]);
        }
    }

    public function afterCreate()
    {
        if ($this->is_public && !$this->parent_id) {
            UserEventLog::add(UserEventLog::TYPE_PROJECT_MESSAGE, [
                'user' => $this->user,
                'otherUser' => $this->project->user,
                'related' => $this
            ]);
        }
    }

    public function isProjectOwner()
    {
        if (!$this->project) {
            return false;
        }

        return $this->project->user_id == $this->user_id;
    }

    /**
     * Can the user modify this message.
     */
    public function canEdit($user = null)
    {
        if (!$user = Auth::getUser()) {
            return false;
        }

        return $this->user_id == $user->id;
    }

    /**
     * Can the user reply to another user message.
     */
    public function canReply($user = null)
    {
        if (!$user = Auth::getUser()) {
            return false;
        }

        if ($this->isOwner($user)) {
            return false;
        }

        return true;
    }

}
