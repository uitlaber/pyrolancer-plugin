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
        'worker'   => ['Ahoy\Pyrolancer\Models\Worker', 'key' => 'user_id', 'otherKey' => 'user_id'],
        'user'     => ['RainLab\User\Models\User'],
    ];

    public $hasMany = [
        'items' => ['Ahoy\Pyrolancer\Models\PortfolioItem', 'order' => 'is_primary desc'],
    ];

    public $hasOne = [
        'primary_item'  => ['Ahoy\Pyrolancer\Models\PortfolioItem', 'conditions' => 'is_primary = 1'],
    ];

    /**
     * Ensures this portfolio has a primary item
     * @return bool
     */
    public function checkPrimaryItem()
    {
        if (!$this->items->count()) {
            return;
        }

        $primaryItem = array_first($this->items, function($key, $item) {
            return $item->is_primary;
        });

        if ($primaryItem) {
            return true;
        }

        $this->items->first()->makePrimary();
        return false;
    }

    /**
     * Automatically creates a worker portfolio if not one already.
     * @param  Ahoy\Pyrolancer\Models\Worker $user
     * @return Ahoy\Pyrolancer\Models\Portfolio
     */
    public static function getFromWorker($worker = null)
    {
        if ($worker === null) {
            $worker = Worker::getFromUser();
        }

        if (!$worker) {
            return null;
        }

        if (!$worker->portfolio) {
            $portfolio = new static;
            $portfolio->user = $worker->user;
            $portfolio->save();

            $worker->portfolio = $portfolio;
        }

        return $worker->portfolio;
    }

    public function completePortfolio()
    {
        if ($this->is_visible) {
            return false;
        }

        $this->worker->has_portfolio = true;
        $this->worker->save();

        $this->is_visible = true;
        $this->save();

        $this->checkPrimaryItem();

        UserEventLog::add(UserEventLog::TYPE_PORTFOLIO_CREATED, [
            'user' => $this->user,
            'related' => $this,
            'createdAt' => $this->created_at
        ]);
    }

    public function hasPortfolio()
    {
        $result = $this->items->count() > 0;

        if (
            ($result && !$this->is_visible) ||
            (!$result && $this->is_visible)
        ) {
            $this->worker->has_portfolio = !!$result;
            $this->worker->save();

            $this->is_visible = !!$result;
            $this->save();
        }

        return $result;
    }

    //
    // Scopes
    //

    public function scopeApplyVisible($query)
    {
        return $query->where('is_visible', true);
    }

}