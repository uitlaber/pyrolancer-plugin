<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;
use Markdown;
use Ahoy\Pyrolancer\Classes\Usher;

/**
 * Worker Model
 */
class Worker extends Model
{

    use \Ahoy\Traits\UrlMaker;
    use \Ahoy\Traits\ModelUtils;
    use \Ahoy\Traits\GeneralUtils;
    use \Ahoy\Pyrolancer\Traits\GeoModel;
    use \Ahoy\Pyrolancer\Traits\UserProxyModel;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    /*
     * Validation
     */
    public $rules = [
        'business_name' => 'required',
    ];

    /**
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['last_digest_at'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_workers';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'business_name',
        'tagline',
        'description',
        'address',
        'latitude',
        'longitude',
        'vicinity',
        'contact_email',
        'contact_phone',
        'website_url',
        'budget',
        'fallback_location',
    ];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['rating_breakdown'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user'         => ['RainLab\User\Models\User'],
        'budget'       => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'worker.budget'"],
        'category'     => ['Ahoy\Pyrolancer\Models\SkillCategory'],
        'vicinity_obj' => ['Ahoy\Pyrolancer\Models\Vicinity', 'key' => 'vicinity_id'],
    ];

    public $belongsToMany = [
        'skills' => ['Ahoy\Pyrolancer\Models\Skill', 'table' => 'ahoy_pyrolancer_workers_skills', 'order' => 'name']
    ];

    public $hasMany = [
        'reviews' => ['Ahoy\Pyrolancer\Models\WorkerReview', 'key' => 'user_id', 'otherKey' => 'user_id']
    ];

    public $hasOne = [
        'portfolio' => ['Ahoy\Pyrolancer\Models\Portfolio', 'key' => 'user_id', 'otherKey' => 'user_id']
    ];

    public $attachOne = [
        'logo' => ['System\Models\File']
    ];

    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'created_at desc' => 'Join date (descending)',
        'created_at asc' => 'Join date (ascending)',
        'updated_at desc' => 'Last updated (descending)',
        'updated_at asc' => 'Last updated (ascending)',
        'name desc' => 'Name (descending)',
        'name asc' => 'Name (ascending)',
    );

    /**
     * @var array Auto generated slug
     */
    public $slugs = ['slug' => 'business_name'];

    /**
     * @var string The component to use for generating URLs.
     */
    protected $urlComponentName = 'profile';

    /**
     * @var string The property name to determine a primary component.
     */
    protected $urlComponentProperty = 'isPrimaryWorker';

    /**
     * Returns an array of values to use in URL generation.
     * @return @array
     */
    public function getUrlParams()
    {
        return [
            'id' => $this->user_id,
            'code' => $this->shortEncodeId($this->user_id)
        ];
    }

    public function afterUpdate()
    {
        if ($this->isDirty('vicinity')) {
            Usher::queueWorkerVicinity($this);
        }
    }

    /**
     * Automatically creates a freelancer profile for a user if not one already.
     * @param  RainLab\User\Models\User $user
     * @return Ahoy\Pyrolancer\Models\Worker
     */
    public static function getFromUser($user = null)
    {
        if ($user === null) {
            $user = Auth::getUser();
        }

        if (!$user) {
            return null;
        }

        if (!$user->worker) {
            $worker = new static;
            $worker->user = $user;
            $worker->forceSave();

            $user->setRelation('worker', $worker);
        }

        return $user->worker;
    }

    public function completeProfile($businessName = null, $skillIds = null)
    {
        if ($this->user->is_worker) {
            return false;
        }

        if ($businessName) {
            $this->business_name = $businessName;
            $this->resetSlug();
        }

        if ($skillIds) {
            $this->skills = $skillIds;
        }

        if ($businessName || $skillIds) {
            $this->save();
        }

        $this->user->is_worker = true;
        $this->user->save();

        UserEventLog::add(UserEventLog::TYPE_WORKER_CREATED, [
            'user' => $this->user,
            'createdAt' => $this->created_at
        ]);
    }

    public function resetSlug()
    {
        if (!$this->isDirty('business_name')) return;
        if ($this->getOriginal('business_name') == $this->business_name) return;
        $this->slug = null;
        $this->slugAttributes();
    }

    public function setRatingStats()
    {
        $overall = 0;
        $total = 0;
        $recommend = 0;
        $breakdown = [];

        foreach ($this->reviews as $review) {
            if (!$review->is_visible) continue;

            $total++;
            $overall += $review->rating;
            if ($review->is_recommend) {
                $recommend++;
            }

            foreach ($review->breakdown as $item => $rating) {
                $breakdown[$item][0] = (int) array_get($breakdown, $item.'.0', 0) + (int) $rating;
                $breakdown[$item][1] = (int) array_get($breakdown, $item.'.1', 0) + 1;
            }
        }

        $finalBreakdown = [];
        foreach ($breakdown as $item => $score) {
            list($bdOverall, $bdTotal) = array_values($score);
            $finalBreakdown[$item] = $bdOverall / $bdTotal;
        }

        $this->rating_overall = $total ? ($overall / $total) : 0;
        $this->rating_breakdown = $finalBreakdown;
        $this->count_ratings = $total;
        $this->count_recommend = $recommend;
    }

    //
    // Attributes
    //

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value;
        $this->attributes['description_html'] = Markdown::parse(trim($value));
    }

    public function getRecommendPercentAttribute()
    {
        if (!$this->count_ratings) return 0;

        return round(($this->count_recommend / $this->count_ratings) * 100);
    }

    //
    // Scopes
    //

    public function scopeApplyVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeApplyPortfolio($query)
    {
        return $query->where('has_portfolio', true);
    }

    /**
     * Lists projects for the front end
     * @param  array $options Display options
     * @return self
     */
    public function scopeListFrontEnd($query, $options = [])
    {
        /*
         * Default options
         */
        extract(array_merge([
            'page'       => 1,
            'perPage'    => 30,
            'sort'       => 'created_at desc',
            'skills'     => null,
            'categories' => null,
            'vicinities' => null,
            'countries'  => null,
            'budgets'    => null,
            'latitude'   => null,
            'longitude'  => null,
            'search'     => ''
        ], $options));

        $searchableFields = ['business_name', 'slug', 'description'];

        /*
         * Sorting
         */
        if (!is_array($sort)) $sort = [$sort];
        foreach ($sort as $_sort) {

            if (in_array($_sort, array_keys(self::$allowedSortingOptions))) {
                $parts = explode(' ', $_sort);
                if (count($parts) < 2) array_push($parts, 'desc');
                list($sortField, $sortDirection) = $parts;

                $query->orderBy($sortField, $sortDirection);
            }
        }

        /*
         * Search
         */
        $search = trim($search);
        if (strlen($search)) {
            $query->searchWhere($search, $searchableFields);
        }

        /*
         * Skills
         */
        if ($skills !== null) {
            if (!is_array($skills)) $skills = [$skills];
            $query->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('id', $skills);
            });
        }

        /*
         * Categories
         */
        if ($categories !== null) {
            if (!is_array($categories)) $categories = [$categories];
            $query->whereIn('category_id', $categories);
        }

        /*
         * Countries
         */
        if ($countries !== null) {
            if (!is_array($countries)) $countries = [$countries];
            $query->whereHas('user', function($q) use ($countries) {
                $q->whereIn('country_id', $countries);
            });
        }

        /*
         * Budgets
         */
        if ($budgets !== null) {
            if (!is_array($budgets)) $budgets = [$budgets];
            $query->whereIn('budget_id', $budgets);
        }

        /*
         * Vicinities
         */
        if ($vicinities !== null) {
            if (!is_array($vicinities)) $vicinities = [$vicinities];
            $query->whereIn('vicinity_id', $vicinities);
        }

        /*
         * Location
         */
        if ($latitude !== null && $longitude != null) {
            $query->applyArea($latitude, $longitude);
        }

        return $query->paginate($perPage, $page);
    }

}
