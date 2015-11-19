<?php namespace Ahoy\Pyrolancer\Models;

use Model;
use Markdown;

/**
 * PortfolioItem Model
 */
class PortfolioItem extends Model
{

    const TYPE_IMAGE = 'image';
    const TYPE_ARTICLE = 'article';
    const TYPE_LINK = 'link';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';

    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
        'type' => 'required',
        'title' => 'required',
        'description' => 'required',
        'uploaded_file' => 'required',
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
        'title',
        'description',
        'sample',
        'link_url',
        'type',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'portfolio' => 'Ahoy\Pyrolancer\Models\Portfolio',
        'type' => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'portfolio.type'"],
    ];

    public $attachOne = [
        'uploaded_file' => 'System\Models\File'
    ];

    public function beforeSave()
    {
        if ($this->isDirty('description')) {
            $this->description_html = Markdown::parse(trim($this->description));
        }

        if ($this->isDirty('sample')) {
            $this->sample_html = Markdown::parse(trim($this->sample));
        }
    }

    public function beforeValidate()
    {
        if ($this->type == 'link') {
            $this->rules['link_url'] = 'required|url';
            $this->rules['uploaded_file'] = null;
        }
    }
}