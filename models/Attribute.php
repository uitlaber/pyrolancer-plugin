<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Attribute Model
 */
class Attribute extends Model
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
    const BID_TYPE = 'bid.type';
    const WORKER_BUDGET = 'worker.budget';
    const PORTFOLIO_TYPE = 'portfolio.type';

    public static $recordCache;
    public static $codeCache;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_attributes';

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

    public function scopeApplyType($query, $type)
    {
        return $query->where('type', $type);
    }

    public static function listAll($type)
    {
        if ($result = array_get(self::$recordCache, $type)) {
            return $result;
        }

        $cache = [];
        $records = self::all();
        foreach ($records as $record) {
            $cache[$record->type][] = $record;
        }

        self::$recordCache = $cache;
        return array_get($cache, $type);
    }

    public static function listType($type)
    {
        if ($result = array_get(self::$recordCache, $type)) {
            return $result;
        }

        $records = self::applyType($type)->get();
        return self::$recordCache[$type] = $records;
    }

    public static function listCodes($type)
    {
        if ($cached = array_get(self::$codeCache, $type)) {
            return $cached;
        }

        $records = self::applyType($type)->lists('id', 'code');
        return self::$codeCache[$type] = $records;
    }

    public function listProjectStatuses()
    {
        return self::listType(self::PROJECT_STATUS)->lists('name', 'id');
    }

    /**
     * Returning the code is more useful than returning JSON.
     */
    public function __toString()
    {
        return $this->code;
    }

}
