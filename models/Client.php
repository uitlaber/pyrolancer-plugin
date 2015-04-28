<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * Client Model
 */
class Client extends Model
{
    use \Ahoy\Traits\GeneralUtils;
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_clients';

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

    /**
     * Automatically creates a client profile for a user if not one already.
     * @param  RainLab\User\Models\User $user
     * @return Ahoy\Pyrolancer\Models\Worker
     */
    public static function getFromUser($user = null)
    {
        if ($user === null)
            $user = Auth::getUser();

        if (!$user)
            return null;

        if (!$user->client) {
            $client = new static;
            $client->user = $user;
            $client->forceSave();

            $user->client = $client;
        }

        return $user->client;
    }

    //
    // Client
    //

    /**
     * Sets the "url" attribute with a URL to this object
     * @param string $pageName
     * @param Cms\Classes\Controller $controller
     */
    public function setUrl($pageName, $controller)
    {
        $params = [
            'id' => $this->id,
            'code' => $this->shortEncodeId($this->id),
            'tab' => 'client'
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }

    public function getNameAttribute()
    {
        $name = $this->user->name;
        $parts = explode(' ', $name);
        $firstPart = array_shift($parts);

        $parts = array_map(function($value) {
            return substr($value, 0, 1) . '.';
        }, $parts);

        array_unshift($parts, $firstPart);

        return implode(' ', $parts);
    }

}