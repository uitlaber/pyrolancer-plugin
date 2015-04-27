<?php namespace Ahoy\Pyrolancer\Models;

use Str;
use Model;

/**
 * WorkerReview Model
 */
class WorkerReview extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
        'user' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_worker_reviews';

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
    protected $jsonable = ['breakdown'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user'     => ['RainLab\User\Models\User'],
        'worker'   => ['Ahoy\Pyrolancer\Models\Worker', 'key' => 'user_id', 'otherKey' => 'user_id'],
    ];

    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'created_at asc' => 'Posted date (ascending)',
        'created_at' => 'Posted date (descending)',
        'updated_at asc' => 'Last updated (ascending)',
        'updated_at' => 'Last updated (descending)',
    );

    public static function createTestimonial($worker, $data)
    {
        $review = new self;
        $review->user_id = $worker->user_id;
        $review->invite_hash = md5(Str::quickRandom());

        $review->rules += [
            'invite_subject' => 'required',
            'invite_email' => 'required|email',
            'invite_message' => 'required',
        ];

        $review->fillable([
            'invite_subject',
            'invite_email',
            'invite_message'
        ]);

        $review->fill($data);
        $review->save();
        return $review;
    }

    public function completeTestimonial($data)
    {
        $this->rules += [
            'invite_name' => 'required',
            'invite_location' => 'required',
            'comment' => 'required',
            'rating' => 'required|numeric|min:1|max:5',
            'is_recommend' => 'boolean',
        ];

        $this->fillable([
            'invite_name',
            'invite_location',
            'comment',
            'breakdown',
            'rating',
            'is_recommend'
        ]);

        $data['rating'] = $this->calculateRating(array_get($data, 'breakdown'));

        $this->fill($data);
        $this->is_visible = true;
        $this->save();

        // @todo This could be deferred to the Queue
        $this->worker->setRatingStats();
        $this->worker->save();

        return $this;
    }

    /**
     * Calculates an overall rating based on the breakdown
     */
    protected function calculateRating($breakdown)
    {
        $count = 0;
        $total = 0;
        foreach ($breakdown as $type => $rating) {
            if (is_null($rating)) continue;
            $count++;
            $total += (int) $rating;
        }

        return $total / $count;
    }

    //
    // Scopes
    //

    public function scopeIsVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Lists reviews for the front end
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
            'sort'       => 'created_at',
            'users'      => null,
            'search'     => '',
            'visible'    => true
        ], $options));

        $searchableFields = ['name', 'comment'];

        if ($visible)
            $query->isVisible();

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
         * Users
         */
        if ($users !== null) {
            if (!is_array($users)) $users = [$users];
            $query->whereIn('user_id', $users);
        }

        return $query->paginate($perPage, $page);
    }

}