<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;

/**
 * Worker Model
 */
class Worker extends Model
{

    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
        'business_name' => 'required',
    ];

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
        'description',
        'address',
        'latitude',
        'longitude',
        'contact_email',
        'contact_phone',
        'website_url',
        'budget',
    ];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['rating_breakdown'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user'     => ['RainLab\User\Models\User'],
        'budget'   => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'worker.budget'"],
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
     * @var array Auto generated slug
     */
    public $slugs = ['slug' => 'business_name'];

    /**
     * Automatically creates a freelancer profile for a user if not one already.
     * @param  RainLab\User\Models\User $user
     * @return Ahoy\Pyrolancer\Models\Worker
     */
    public static function getFromUser($user = null)
    {
        if ($user === null)
            $user = Auth::getUser();

        if (!$user)
            return null;

        if (!$user->worker) {
            $worker = new static;
            $worker->user = $user;
            $worker->forceSave();

            $user->worker = $worker;
        }

        return $user->worker;
    }

    public function getRecommendPercentAttribute()
    {
        if (!$this->count_ratings) return 0;

        return round(($this->count_recommend / $this->count_ratings) * 100);
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

        $this->rating_overall = $overall / $total;
        $this->rating_breakdown = $finalBreakdown;
        $this->count_ratings = $total;
        $this->count_recommend = $recommend;
    }

}