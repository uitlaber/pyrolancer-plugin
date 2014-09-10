<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Project Model
 */
class Project extends Model
{

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
        'category' => ['Responsiv\Pyrolancer\Models\Category'],
        'project_type' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'project.type'"],
        'position_type' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'position.type'"],
        'budget_type' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.type'"],
        'budget_fixed' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.fixed'"],
        'budget_hourly' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.hourly'"],
        'budget_timeframe' => ['Responsiv\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'budget.timeframe'"],
    ];

}