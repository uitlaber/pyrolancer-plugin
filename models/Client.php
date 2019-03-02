<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Client Model
 */
class Client extends Model
{
    use \Cms\Traits\UrlMaker;
    use \Responsiv\Pyrolancer\Traits\GeneralUtils;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    /*
     * Validation
     */
    public $rules = [];

    /**
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['last_digest_at'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_clients';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

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

    public $hasMany = [
        'reviews' => [
            'Responsiv\Pyrolancer\Models\WorkerReview',
            'key' => 'client_user_id',
            'otherKey' => 'user_id'
        ],
        'projects' => [
            'Responsiv\Pyrolancer\Models\Project',
            'key' => 'user_id',
            'otherKey' => 'user_id',
            'delete' => true,
            'softDelete' => true
        ],
    ];

    /**
     * @var string The component to use for generating URLs.
     */
    protected $urlComponentName = 'profile';

    /**
     * @var string The property name to determine a primary component.
     */
    protected $urlComponentProperty = 'isPrimaryClient';

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

    /**
     * Automatically creates a client profile for a user if not one already.
     * @param  RainLab\User\Models\User $user
     * @return Responsiv\Pyrolancer\Models\Worker
     */
    public static function getFromUser($user = null)
    {
        if ($user === null)
            $user = Auth::getUser();

        if (!$user)
            return null;

        $displayName = self::makeDisplayName($user);

        if (!$user->client) {
            $client = new static;
            $client->user = $user;
            $client->display_name = $displayName;
            $client->forceSave();

            $user->client = $client;
        }
        elseif ($user->client->display_name != $displayName) {
            $client = $user->client;
            $client->display_name = $displayName;
            $client->forceSave();
        }

        return $user->client;
    }

    /**
     * Generates a display name from a user's name.
     * @return string
     */
    public static function makeDisplayName($user)
    {
        $name = trim($user->name . ' ' . $user->surname);
        $parts = explode(' ', $name);
        $firstPart = array_shift($parts);

        $parts = array_map(function($value) {
            return substr($value, 0, 1) . '.';
        }, $parts);

        array_unshift($parts, $firstPart);

        return implode(' ', $parts);
    }

    public function setRatingStats()
    {
        $overall = 0;
        $total = 0;

        foreach ($this->reviews as $review) {
            if (!$review->client_is_visible) continue;

            $total++;
            $overall += $review->client_rating;
        }

        $this->rating_overall = $total ? ($overall / $total) : 0;
        $this->count_ratings = $total;

        $this->count_projects = $this->projects()->applyVisible()->count();
        $this->count_projects_active = $this->projects()->applyActive()->count();
    }

}
