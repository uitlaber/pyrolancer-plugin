<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Freelancer Model
 */
class Freelancer extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_freelancers';

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
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

}