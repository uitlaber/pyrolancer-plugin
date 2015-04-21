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
     * @var array Relations
     */
    public $belongsTo = [
        'user'     => ['RainLab\User\Models\User'],
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

        $review->fillable(['invite_subject', 'invite_email', 'invite_message']);
        $review->fill($data);
        $review->save();
        return $review;
    }

}