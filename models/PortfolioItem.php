<?php namespace Responsiv\Pyrolancer\Models;

use Model;
use Markdown;
use ValidationException;

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

    use \Responsiv\Pyrolancer\Traits\ModelUtils;
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
    public $table = 'responsiv_pyrolancer_portfolio_items';

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
        'is_primary',
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'portfolio' => 'Responsiv\Pyrolancer\Models\Portfolio',
        'type' => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'portfolio.type'"],
    ];

    public $attachOne = [
        'uploaded_file' => 'System\Models\File'
    ];

    public function afterCreate()
    {
        if ($this->is_primary) {
            $this->makePrimary();
        }
    }

    public function beforeUpdate()
    {
        if ($this->isDirty('is_primary')) {
            $this->makePrimary();

            if (!$this->is_primary) {
                throw new ValidationException(['is_primary' => 'Cannot unset primary portfolio item.']);
            }
        }
    }

    public function afterDelete()
    {
        $this->portfolio->checkPrimaryItem();
    }

    /**
     * Makes this model the default
     * @return void
     */
    public function makePrimary()
    {
        static::where('portfolio_id', $this->portfolio_id)
            ->where('id', $this->id)
            ->update(['is_primary' => true])
        ;

        static::where('portfolio_id', $this->portfolio_id)
            ->where('id', '<>', $this->id)
            ->update(['is_primary' => false])
        ;
    }

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
