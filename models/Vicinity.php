<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Vicinity Model
 */
class Vicinity extends Model
{
    use \Responsiv\Pyrolancer\Traits\GeoModel;
    use \October\Rain\Database\Traits\Sluggable;

    public $implement = ['RainLab.Location.Behaviors.LocationModel'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_vicinities';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['data'];

    /**
     * @var array Sluggable fields
     */
    public $slugs = [
        'slug' => 'name'
    ];

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

    public static function processProjectVicinity($project)
    {
        $vicinity = static::createFromObject($project);
        if (!$vicinity) {
            return;
        }

        $project
            ->newQuery()
            ->where('id', $project->id)
            ->update(['vicinity_id' => $vicinity->id])
        ;

        $vicinity->rebuildStats();
        $vicinity->save();
    }

    public static function processWorkerVicinity($worker)
    {
        $vicinity = static::createFromObject($worker);
        if (!$vicinity) {
            return;
        }

        $worker
            ->newQuery()
            ->where('id', $worker->id)
            ->update(['vicinity_id' => $vicinity->id])
        ;

        $vicinity->rebuildStats();
        $vicinity->save();
    }

    /**
     * Registers a vicinity from an object. The object should contain:
     * - vicinity
     * - latitude
     * - longitude
     * - country_id
     * - state_id
     */
    public static function createFromObject($object)
    {
        if (!$object->vicinity || !$object->latitude || !$object->longitude) {
            return false;
        }

        $name = trim($object->vicinity);
        $latLng = [$object->latitude, $object->longitude];

        /*
         * Find existing model
         */
        $query = static::where('name', $name);
        if ($object->country_id && $object->state_id) {
            $query = $query
                ->where('country_id', $object->country_id)
                ->where('state_id', $object->state_id)
            ;
        }
        $model = $query->first();

        /*
         * Ensure geo points are unique
         */
        $geoPoints = $model ? (array) $model->data : [];
        $geoPoints = array_filter($geoPoints, function($val) use ($latLng) {
            return $val != $latLng;
        });
        array_unshift($geoPoints, $latLng);

        list($lat, $lng) = $model ? $model->makeAreaAverage($geoPoints) : $latLng;

        /*
         * Create new model
         */
        if (!$model) {
            $model = new static;
            $model->name = $name;

            if ($object->country_id && $object->state_id) {
                $model->state_id = $object->state_id;
                $model->country_id = $object->country_id;
            }
        }

        /*
         * Cap total points, add the area average when popping
         */
        $maxPoints = 25;
        if (count($geoPoints) > $maxPoints) {
            $geoPoints = array_slice($geoPoints, 0, $maxPoints - 1);
            array_push($geoPoints, [$lat, $lng]);
        }

        $model->latitude = $lat;
        $model->longitude = $lng;
        $model->data = $geoPoints;
        $model->save();

        return $model;
    }

    public function rebuildStats()
    {
        $this->count_workers = Worker::where('vicinity_id', $this->id)->count();
        $this->count_portfolios = Worker::where('vicinity_id', $this->id)->applyPortfolio()->count();
        $this->count_projects = Project::where('vicinity_id', $this->id)->count();
    }

}
