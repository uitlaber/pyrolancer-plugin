<?php namespace Ahoy\Pyrolancer\Models;

use Model;
use Markdown;

/**
 * ProjectExtraDetail Model
 */
class ProjectExtraDetail extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_project_extra_details';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /*
     * Validation
     */
    public $rules = [
        'description' => 'required',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'project' => ['Ahoy\Pyrolancer\Models\Project'],
    ];

    public function beforeSave()
    {
        if ($this->isDirty('description'))
            $this->description_html = Markdown::parse(trim($this->description));
    }

}