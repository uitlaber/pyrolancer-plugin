<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * PortfolioItem Model
 */
class PortfolioItem extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_portfolio_items';

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
        'portfolio' => ['Ahoy\Pyrolancer\Models\Portfolio'],
    ];

    public $attachOne = [
        'image' => ['System\Models\File']
    ];
}