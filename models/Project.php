<?php namespace Responsiv\Pyrolancer\Models;

use Auth;
use Model;

/**
 * Project Model
 */
class Project extends Model
{

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
        'extra_details'    => ['Responsiv\Pyrolancer\Models\ProjectExtraDetail'],
        'messages'         => ['Responsiv\Pyrolancer\Models\ProjectMessage'],
    ];

    public $belongsTo = [
        'category'         => ['Responsiv\Pyrolancer\Models\ProjectCategory'],
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

    public function canEdit($user = null)
    {
        return $this->isOwner($user);
    }

    public function isOwner($user = null)
    {
        if (!$user)
            $user = Auth::getUser();

        if (!$user)
            return false;

        return $this->user_id == $user->id;
    }

}