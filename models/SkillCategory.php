<?php namespace Ahoy\Pyrolancer\Models;

use ActivRecord;

/**
 * Project Category Model
 */
class SkillCategory extends ActivRecord
{

    use \October\Rain\Database\Traits\Sortable;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\SimpleTree;

    const PARENT_ID = 'parent_id';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_skill_categories';

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
    public $hasMany = [
        'skills' => [
            'Ahoy\Pyrolancer\Models\Skill',
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

}