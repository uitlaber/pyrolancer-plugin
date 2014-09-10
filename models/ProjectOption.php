<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * ProjectOption Model
 */
class ProjectOption extends Model
{
    use \October\Rain\Database\Traits\Sortable;

    const PROJECT_TYPE = 'project.type';
    const POSITION_TYPE = 'position.type';
    const BUDGET_TYPE = 'budget.type';
    const BUDGET_FIXED = 'budget.fixed';
    const BUDGET_HOURLY = 'budget.hourly';
    const BUDGET_TIMEFRAME = 'budget.timeframe';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_project_options';

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
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeForType($query, $type)
    {
        return $query->where('type', $type);
    }

}