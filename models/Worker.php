<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;

/**
 * Worker Model
 */
class Worker extends Model
{

    use \October\Rain\Database\Traits\Sluggable;

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
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => ['RainLab\User\Models\User']
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'skills' => ['Ahoy\Pyrolancer\Models\Skill', 'table' => 'ahoy_pyrolancer_workers_skills', 'order' => 'name']
    ];

    /**
     * @var array Auto generated slug
     */
    public $slugs = ['slug' => 'username'];

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
            $worker->save();

            $user->worker = $worker;
        }

        return $user->worker;
    }

}