<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Project Model
 */
class Project extends Model
{

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
     * @var array Relations
     */
    public $belongsToMany = [
        'skills' => ['Responsiv\Pyrolancer\Models\Skill', 'table' => 'responsiv_pyrolancer_projects_skills', 'order' => 'name']
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

}