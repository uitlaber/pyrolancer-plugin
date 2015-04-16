<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * PortfolioItem Model
 */
class PortfolioItem extends Model
{
    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
        'image' => 'required',
        'description' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_portfolio_items';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'description',
        'website_url',
    ];

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