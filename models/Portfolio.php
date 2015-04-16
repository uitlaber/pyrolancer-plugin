<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * Portfolio Model
 */
class Portfolio extends Model
{

    use \Ahoy\Traits\ModelUtils;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_portfolios';

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
        'items'    => ['Ahoy\Pyrolancer\Models\PortfolioItem'],
    ];

    /**
     * Automatically creates a worker portfolio if not one already.
     * @param  Ahoy\Pyrolancer\Models\Worker $user
     * @return Ahoy\Pyrolancer\Models\Portfolio
     */
    public static function getFromWorker($worker = null)
    {
        if ($worker === null)
            $worker = Worker::getFromUser();

        if (!$worker)
            return null;

        if (!$worker->portfolio) {
            $portfolio = new static;
            $portfolio->user = $worker->user;
            $portfolio->save();

            $worker->portfolio = $portfolio;
        }

        return $worker->portfolio;
    }

}