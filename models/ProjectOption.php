<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * ProjectOption Model
 */
class ProjectOption extends Model
{
    use \October\Rain\Database\Traits\Sortable;

    const PROJECT_STATUS = 'project.status';
    const PROJECT_TYPE = 'project.type';
    const POSITION_TYPE = 'position.type';
    const BUDGET_TYPE = 'budget.type';
    const BUDGET_FIXED = 'budget.fixed';
    const BUDGET_HOURLY = 'budget.hourly';
    const BUDGET_TIMEFRAME = 'budget.timeframe';
    const BID_STATUS = 'bid.status';

    public static $recordCache;
    public static $codeCache;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_project_options';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    public function getLabelAttribute($value)
    {
        return strlen($value) ? $value : $this->name;
    }

    public function scopeForType($query, $type)
    {
        return $query->where('type', $type);
    }

    public static function listAll($type)
    {
        if (self::$recordCache !== null) {
            return array_get(self::$recordCache, $type);
        }

        $cache = [];
        $records = self::all();
        foreach ($records as $record) {
            $cache[$record->type][] = $record;
        }

        self::$recordCache = $cache;
        return array_get($cache, $type);
    }

    public static function listCodes($type)
    {
        if ($cached = array_get(self::$codeCache, $type)) {
            return $cached;
        }

        $cache = [];
        $records = self::listAll($type);
        foreach ($records as $record) {
            $cache[$record->code] = $record->id;
        }

        return self::$codeCache[$type] = $cache;
    }

}