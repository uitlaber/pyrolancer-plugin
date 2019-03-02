<?php namespace Responsiv\Pyrolancer\Models;

use Model;

/**
 * Project Category Model
 */
class ProjectCategory extends Model
{

    use \October\Rain\Database\Traits\Sortable;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\SimpleTree;

    const PARENT_ID = 'parent_id';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_project_categories';

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
        'skills' => [
            'Responsiv\Pyrolancer\Models\Skill',
            'table' => 'responsiv_pyrolancer_project_categories_skills',
            'key' => 'category_id',
            'order' => 'name'
        ]
    ];

    public function scopeApplyVisible($query)
    {
        return $query
            ->whereNotNull('is_visible')
            ->where('is_visible', '=', 1)
        ;
    }

    public function beforeDelete()
    {
        /*
         * Clean up child categories
         */
        $children = $this->children;
        if ($children && $children->count()) {
            $children->each(function($child) {
                $child->delete();
            });
        }
    }

}
