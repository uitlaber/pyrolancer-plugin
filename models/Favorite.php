<?php namespace Responsiv\Pyrolancer\Models;

use Str;
use Model;
use Session;

/**
 * Favorite Model
 */
class Favorite extends Model
{

    const SESSION_KEY = 'responsiv.pyrolancer.favorites';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_favorites';

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
        'user'           => ['RainLab\User\Models\User'],
    ];

    public $belongsToMany = [
        'workers' => ['Responsiv\Pyrolancer\Models\Worker', 'table' => 'responsiv_pyrolancer_favorites_workers']
    ];

    public static function createList($user = null)
    {
        if ($list = static::listFromUser($user)) {
            return $list;
        }

        $list = new static;

        if ($user) {
            $list->user = $user;
        }

        $list->save();

        Session::put(self::SESSION_KEY, $list->hash);

        return $list;
    }

    public static function listFromKey($publicKey = null)
    {
        $id = (int) substr($publicKey, 8);
        if (!$list = static::where('id', $id)->first()) {
            return null;
        }

        if (!Str::equals($list->public_key, $publicKey)) {
            return null;
        }
        return $list;
    }

    public static function listFromUser($user = null)
    {
        $hasSession = Session::has(self::SESSION_KEY);
        if (!$user && !$hasSession) {
            return false;
        }

        if ($hasSession) {
            return static::where('hash', Session::get(self::SESSION_KEY))->first();
        }
        else {
            return static::where('user_id', $user->id)->first();
        }
    }

    public function beforeCreate()
    {
        $this->generateHash();
    }

    public function getPublicKeyAttribute()
    {
        return strtolower(substr($this->hash, 0, 8)) . $this->id;
    }

    /**
     * Internal helper, and set generate a unique hash for this list.
     * @return string
     */
    protected function generateHash()
    {
        $this->hash = $this->createHash();
        while ($this->newQuery()->where('hash', $this->hash)->count() > 0) {
            $this->hash = $this->createHash();
        }
    }

    /**
     * Internal helper, create a hash for this list.
     * @return string
     */
    protected function createHash()
    {
        return md5(uniqid('favorites', microtime()));
    }

}
