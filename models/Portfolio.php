<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * Portfolio Model
 */
class Portfolio extends Model
{

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

}