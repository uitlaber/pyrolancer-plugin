<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;
use Markdown;

/**
 * Project Model
 */
class Project extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Validation;
    use \Responsiv\Geolocation\Traits\LocationCode;

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_projects';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Sluggable fields
     */
    public $slugs = [
        'slug' => 'name'
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'skills' => ['Ahoy\Pyrolancer\Models\Skill', 'table' => 'ahoy_pyrolancer_projects_skills', 'order' => 'name']
    ];

    public $hasMany = [
        'bids'             => ['Ahoy\Pyrolancer\Models\ProjectBid'],
        'extra_details'    => ['Ahoy\Pyrolancer\Models\ProjectExtraDetail'],
        'messages'         => ['Ahoy\Pyrolancer\Models\ProjectMessage', 'conditions' => "parent_id is null"],
        'status_log'       => ['Ahoy\Pyrolancer\Models\ProjectStatusLog', 'order' => 'id desc'],
    ];

    public $belongsTo = [
        'category'         => ['Ahoy\Pyrolancer\Models\ProjectCategory'],
        'status'           => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'project.status'"],
        'project_type'     => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'project.type'"],
        'position_type'    => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'position.type'"],
        'budget_type'      => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.type'"],
        'budget_fixed'     => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.fixed'"],
        'budget_hourly'    => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.hourly'"],
        'budget_timeframe' => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.timeframe'"],
        'country'          => ['RainLab\User\Models\Country'],
        'state'            => ['RainLab\User\Models\State'],
        'user'             => ['RainLab\User\Models\User'],
    ];

    public $attachMany = [
        'files' => ['System\Models\File'],
    ];

    // public function beforeSave()
    // {
    //     if ($this->isDirty('description'))
    //         $this->description_html = Markdown::parse(trim($this->description));

    //     if ($this->isDirty('instructions'))
    //         $this->instructions_html = Markdown::parse(trim($this->instructions));
    // }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value;
        $this->attributes['description_html'] = Markdown::parse(trim($value));
    }

    public function setInstructionsAttribute($value)
    {
        $this->attributes['instructions'] = $value;
        $this->attributes['instructions_html'] = Markdown::parse(trim($value));
    }

    public function beforeCreate()
    {
        if (!$this->status_id) {
            $this->status = ProjectOption::forType(ProjectOption::PROJECT_STATUS)
                ->whereCode(self::STATUS_DRAFT)
                ->first();
        }
    }

    public function getBackendUrl()
    {
        return \Backend::url('ahoy/pyrolancer/projects/preview/'.$this->id);
    }

    public function getUrl()
    {
        return \Cms\Classes\Page::url('project', [
            'id' => $this->id,
            'slug' => $this->slug,
        ]);
    }

    /**
     * Can the user bid on this project
     */
    public function canBid($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        if ($this->isOwner($user))
            return false;

        return true;
    }

    public function hasBid($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        $userBid = $this->bids->first(function($key, $bid) use ($user) {
            return $bid->user_id == $user->id;
        });

        return !is_null($userBid);
    }

    /**
     * Can new messages be posted to this project
     */
    public function canMessage($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        return true;
    }

    /**
     * Can the user, or logged in user, edit this project.
     */
    public function canEdit($user = null)
    {
        return $this->isOwner($user);
    }

    public function scopeApplyOwner($query, $user = null)
    {
        if (!$user = $this->lookupUser($user))
            return $query->whereRaw('1 = 2');

        return $query->where('user_id', $user->id);
    }

    //
    // Status workflow
    //

    public function markSubmitted()
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_PENDING);
    }

    public function markApproved()
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_ACTIVE);
    }

    public function markRejected()
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_REJECTED);
    }

    public function markSuspended()
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_SUSPENDED);
    }

}