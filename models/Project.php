<?php namespace Responsiv\Pyrolancer\Models;

use Auth;
use Model;

/**
 * Project Model
 */
class Project extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    use \October\Rain\Database\Traits\Sluggable;
    use \Responsiv\Geolocation\Traits\LocationCode;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_projects';

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
        'skills' => ['Responsiv\Pyrolancer\Models\Skill', 'table' => 'responsiv_pyrolancer_projects_skills', 'order' => 'name']
    ];

    public $hasMany = [
        'bids'             => ['Responsiv\Pyrolancer\Models\ProjectBid'],
        'extra_details'    => ['Responsiv\Pyrolancer\Models\ProjectExtraDetail'],
        'messages'         => ['Responsiv\Pyrolancer\Models\ProjectMessage', 'conditions' => "parent_id is null"],
    ];

    public $belongsTo = [
        'category'         => ['Responsiv\Pyrolancer\Models\ProjectCategory'],
        'status'           => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'project.status'"],
        'project_type'     => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'project.type'"],
        'position_type'    => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'position.type'"],
        'budget_type'      => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.type'"],
        'budget_fixed'     => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.fixed'"],
        'budget_hourly'    => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.hourly'"],
        'budget_timeframe' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.timeframe'"],
        'country'          => ['RainLab\User\Models\Country'],
        'state'            => ['RainLab\User\Models\State'],
        'user'             => ['RainLab\User\Models\User'],
    ];

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

    public function isOwner($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        return $this->user_id == $user->id;
    }

    protected function lookupUser($user = null)
    {
        if (!$user)
            $user = Auth::getUser();

        if (!$user)
            return false;

        return $user;
    }

}