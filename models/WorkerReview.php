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

}