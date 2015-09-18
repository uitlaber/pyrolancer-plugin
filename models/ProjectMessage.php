<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;
use Markdown;

/**
 * ProjectMessage Model
 */
class ProjectMessage extends Model
{
    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\SimpleTree;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDeleting;

    const TREE_LABEL = 'created_at';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_project_messages';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

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
        'project' => ['Ahoy\Pyrolancer\Models\Project'],
        'user'    => ['RainLab\User\Models\User'],
        'worker' => ['Ahoy\Pyrolancer\Models\Worker', 'key' => 'user_id', 'otherKey' => 'user_id'],
        'client' => ['Ahoy\Pyrolancer\Models\Client', 'key' => 'user_id', 'otherKey' => 'user_id'],
    ];

    public function beforeSave()
    {
        if ($this->isDirty('content'))
            $this->content_html = Markdown::parse(trim($this->content));
    }

    public function isProjectOwner()
    {
        if (!$this->project)
            return false;

        return $this->project->user_id == $this->user_id;
    }

    /**
     * Can the user modify this message.
     */
    public function canEdit($user = null)
    {
        if (!$user)
            $user = Auth::getUser();

        if (!$user)
            return false;

        return $this->user_id == $user->id;
    }

}