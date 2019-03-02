<?php namespace Responsiv\Pyrolancer\Models;

use Url;
use Auth;
use Markdown;
use Model;
use Responsiv\Pyrolancer\Classes\Usher;

/**
 * Worker Model
 */
class Worker extends Model
{

    use \Cms\Traits\UrlMaker;
    use \Responsiv\Pyrolancer\Traits\ModelUtils;
    use \Responsiv\Pyrolancer\Traits\GeneralUtils;
    use \Responsiv\Pyrolancer\Traits\GeoModel;
    use \Responsiv\Pyrolancer\Traits\UserProxyModel;
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
    public $table = 'responsiv_pyrolancer_workers';

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
        'category_id'
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
        'budget'       => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'worker.budget'"],
        'category'     => ['Responsiv\Pyrolancer\Models\SkillCategory'],
        'vicinity_obj' => ['Responsiv\Pyrolancer\Models\Vicinity', 'key' => 'vicinity_id'],
    ];

    public $belongsToMany = [
        'skills' => ['Responsiv\Pyrolancer\Models\Skill', 'table' => 'responsiv_pyrolancer_workers_skills', 'order' => 'name']
    ];

    public $hasMany = [
        'reviews' => ['Responsiv\Pyrolancer\Models\WorkerReview', 'key' => 'user_id', 'otherKey' => 'user_id']
    ];

    public $hasOne = [
        'portfolio' => ['Responsiv\Pyrolancer\Models\Portfolio', 'key' => 'user_id', 'otherKey' => 'user_id', 'delete' => true]
    ];

    public $attachOne = [
        'logo' => ['System\Models\File']
    ];

    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'rating_overall desc' => 'Bested rated',
        'count_ratings desc' => 'Most rated',
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

    public function beforeValidate()
    {
        if (!$this->latitude) {
            $this->latitude = null;
        }

        if (!$this->longitude) {
            $this->longitude = null;
        }
    }

    /**
     * Automatically creates a freelancer profile for a user if not one already.
     * @param  RainLab\User\Models\User $user
     * @return Responsiv\Pyrolancer\Models\Worker
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

    public function getWebsiteLinkAttribute()
    {
        if (!$url = $this->website_url) {
            return $url;
        }

        if (Url::isValidUrl($url)) {
            return $url;
        }

        return '//'.$url;
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
            'sort'       => null,
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
        if ($sort) {
            if (!is_array($sort)) $sort = [$sort];
            foreach ($sort as $_sort) {

                if (in_array($_sort, array_keys(self::$allowedSortingOptions))) {
                    $parts = explode(' ', $_sort);
                    if (count($parts) < 2) array_push($parts, 'desc');
                    list($sortField, $sortDirection) = $parts;

                    $query->orderBy($sortField, $sortDirection);
                }
            }
        }
        else {
            // Defaults
            $query->orderBy('count_ratings', 'desc');
            $query->orderBy('rating_overall', 'desc');
            $query->orderBy('count_bids', 'desc');
            $query->orderBy('updated_at', 'desc');
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
