<?php namespace Responsiv\Pyrolancer\Models;

use Auth;
use Model;
use ApplicationException;
use Responsiv\Pyrolancer\Models\Attribute;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Markdown;
use Exception;

/**
 * ProjectBid Model
 */
class ProjectBid extends Model
{

    use \Responsiv\Pyrolancer\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Validation;

    const TYPE_FIXED = 'fixed';
    const TYPE_HOURLY = 'hourly';

    /*
     * Validation
     */
    public $rules = [
        'details' => 'required',
        'type' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'responsiv_pyrolancer_project_bids';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'details',
        'hourly_rate',
        'hourly_hours',
        'fixed_rate',
        'fixed_days',
        'type'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user'    => ['RainLab\User\Models\User'],
        'worker'  => ['Responsiv\Pyrolancer\Models\Worker'],
        'project' => ['Responsiv\Pyrolancer\Models\Project'],
        'type'    => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'bid.type'"],
    ];

    public function beforeSave()
    {
        if ($this->isDirty('details')) {
            $this->details_html = Markdown::parse(trim($this->details));
        }

        $this->total_estimate = $this->calcTotalEstimate();
    }

    public function beforeValidate()
    {
        if ($this->type == self::TYPE_FIXED) {
            $this->hourly_rate = 0;
            $this->hourly_hours = null;
            $this->rules['fixed_rate'] = 'required|numeric';
            $this->rules['fixed_days'] = 'required|numeric';
        }
        else {
            $this->fixed_rate = 0;
            $this->fixed_days = null;
            $this->rules['hourly_rate'] = 'required|numeric';
            $this->rules['hourly_hours'] = 'required|numeric';
        }
    }

    public function afterDelete()
    {
        UserEventLog::remove(UserEventLog::TYPE_PROJECT_BID, ['related' => $this]);
    }

    public function afterCreate()
    {
        UserEventLog::add(UserEventLog::TYPE_PROJECT_BID, [
            'user' => $this->user,
            'otherUser' => $this->project->user,
            'related' => $this
        ]);
    }

    public static function makeForProject($project, $user = null)
    {
        if ($user === null) {
            $user = Auth::getUser();
        }

        if (!$user) {
            throw new ApplicationException('You must be logged in!');
        }

        if ($bid = $project->hasBid($user)) {
            return $bid;
        }

        $worker = WorkerModel::getFromUser($user);

        $bid = new static;
        $bid->user = $user;
        $bid->project = $project;
        $bid->worker = $worker;
        return $bid;
    }

    public function calcTotalEstimate()
    {
        try {
            if ($this->type == self::TYPE_FIXED) {
                return $this->fixed_rate;
            }
            else {
                return $this->hourly_rate * max($this->hourly_hours, 1);
            }
        }
        catch (Exception $ex) {
            return 0;
        }
    }

}
