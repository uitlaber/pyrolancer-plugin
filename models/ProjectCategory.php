<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Project Category Model
 */
class ProjectCategory extends Model
{

    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\SimpleTree;

    const PARENT_ID = 'parent_id';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_categories';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     */
     protected $slugs = ['slug' => 'name'];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'skills' => ['Responsiv\Pyrolancer\Models\Skill', 'table' => 'responsiv_pyrolancer_categories_skills', 'order' => 'name']
    ];

}