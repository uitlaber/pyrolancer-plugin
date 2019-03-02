<?php namespace Responsiv\Pyrolancer\Models;

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
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['rating_at', 'client_rating_at'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_worker_reviews';

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
        'user'        => 'RainLab\User\Models\User',
        'client_user' => 'RainLab\User\Models\User',
        'project'     => 'Responsiv\Pyrolancer\Models\Project',
        'worker'      => ['Responsiv\Pyrolancer\Models\Worker', 'key' => 'user_id', 'otherKey' => 'user_id'],
        'client'      => ['Responsiv\Pyrolancer\Models\Client', 'key' => 'client_user_id', 'otherKey' => 'user_id'],
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

    public static function getForProject($project)
    {
        $review = self::where('project_id', $project->id)
            ->where('user_id', $project->chosen_user_id)
            ->first();

        if ($review) {
            $review->setRelation('project', $project);
            return $review;
        }

        $review = new static;
        $review->project = $project;
        $review->user_id = $project->chosen_user_id;
        $review->client_user_id = $project->user_id;
        $review->is_testimonial = false;
        $review->forceSave();

        return $review;
    }

    /**
     * Completes a review for the worker
     */
    public function completeWorkerReview($data)
    {
        $this->rules += [
            'rating' => 'required|numeric|min:1|max:5',
            'is_recommend' => 'boolean',
        ];

        $this->fillable([
            'comment',
            'breakdown',
            'rating',
            'is_recommend'
        ]);

        $this->rating_at = $this->freshTimestamp();
        $this->is_visible = true;
        $this->fill($data);
        $this->save();

        // @todo This could be deferred to the Queue
        $this->worker->setRatingStats();
        $this->worker->save();
    }

    /**
     * Completes a review for the client
     */
    public function completeClientReview($data)
    {
        $this->rules += [
            'client_rating' => 'required|numeric|min:1|max:5',
        ];

        $this->fillable([
            'client_comment',
            'client_rating'
        ]);

        $this->client_rating_at = $this->freshTimestamp();
        $this->client_is_visible = true;
        $this->fill($data);
        $this->save();

        // @todo This could be deferred to the Queue
        $this->client->setRatingStats();
        $this->client->save();
    }

    public static function createTestimonial($worker, $data)
    {
        $review = new self;
        $review->is_testimonial = true;
        $review->user_id = $worker->user_id;
        $review->invite_hash = md5(Str::random());

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

        $this->is_visible = true;
        $this->fill($data);
        $this->save();

        // @todo This could be deferred to the Queue
        $this->worker->setRatingStats();
        $this->worker->save();

        return $this;
    }

    public function beforeValidate()
    {
        if ($this->breakdown) {
            $this->rating = $this->calculateRating($this->breakdown);
        }
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

    public function scopeApplyVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeApplyClientVisible($query)
    {
        return $query->where('client_is_visible', true);
    }

    public function scopeApplyTestimonial($query, $value = true)
    {
        return $query->where('is_testimonial', $value);
    }

    public function scopeApplyHybridUser($query, $user)
    {
        $userId = $user->id;

        return $query->where(function($query) use ($userId) {
            $query->where(function($query) use ($userId) {
                $query->where('user_id', $userId);
                $query->where('is_visible', true);
            });
            $query->orWhere(function($query) use ($userId) {
                $query->where('client_user_id', $userId);
                $query->where('client_is_visible', true);
            });
        });
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
            'page'          => 1,
            'perPage'       => 30,
            'sort'          => 'created_at',
            'users'         => null,
            'clientUsers'   => null,
            'search'        => '',
            'visible'       => false,
            'clientVisible' => false
        ], $options));

        $searchableFields = ['name', 'comment'];

        if ($visible) {
            $query->applyVisible();
        }

        if ($clientVisible) {
            $query->applyClientVisible();
        }

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

        /*
         * Client users
         */
        if ($clientUsers !== null) {
            if (!is_array($clientUsers)) $clientUsers = [$clientUsers];
            $query->whereIn('client_user_id', $clientUsers);
        }

        return $query->paginate($perPage, $page);
    }

}
