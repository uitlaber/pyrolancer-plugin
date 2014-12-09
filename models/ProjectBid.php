<?php namespace Ahoy\Pyrolancer\Models;

use Model;
use Ahoy\Pyrolancer\Models\ProjectOption;

/**
 * ProjectBid Model
 */
class ProjectBid extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_ACCEPTED = 'accepted';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_project_bids';

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
        'user'    => ['RainLab\User\Models\User'],
        'project' => ['Ahoy\Pyrolancer\Models\Project'],
        'status'  => ['Ahoy\Pyrolancer\Models\ProjectOption', 'conditions' => "type = 'bid.status'"],
    ];

    public function beforeCreate()
    {
        if (!$this->status_id) {
            $this->status = ProjectOption::forType(ProjectOption::BID_STATUS)
                ->whereCode('active')
                ->first();
        }
    }

}