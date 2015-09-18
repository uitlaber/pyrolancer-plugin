<?php namespace Ahoy\Pyrolancer\Models;

use App;
use Auth;
use Model;
use Backend;
use BackendAuth;
use Cms\Classes\Page as CmsPage;
use Markdown;

/**
 * Project Model
 */
class Project extends Model
{

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Revisionable;

    public $implement = ['RainLab.Location.Behaviors.LocationModel'];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_projects';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Sluggable fields
     */
    public $slugs = [
        'slug' => 'name'
    ];

    /**
     * @var array Monitor these attributes for changes.
     */
    protected $revisionable = ['description', 'instructions'];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'skills' => ['Ahoy\Pyrolancer\Models\Skill', 'table' => 'ahoy_pyrolancer_projects_skills', 'order' => 'name']
    ];

    public $hasMany = [
        'bids'             => ['Ahoy\Pyrolancer\Models\ProjectBid'],
        'messages'         => ['Ahoy\Pyrolancer\Models\ProjectMessage'],
        'status_log'       => ['Ahoy\Pyrolancer\Models\ProjectStatusLog', 'order' => 'id desc'],
    ];

    public $belongsTo = [
        'category'         => ['Ahoy\Pyrolancer\Models\ProjectCategory'],
        'status'           => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'project.status'"],
        'project_type'     => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'project.type'"],
        'position_type'    => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'position.type'"],
        'budget_type'      => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.type'"],
        'budget_fixed'     => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.fixed'"],
        'budget_hourly'    => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.hourly'"],
        'budget_timeframe' => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.timeframe'"],
        'user'             => ['RainLab\User\Models\User'],
        'client'           => ['Ahoy\Pyrolancer\Models\Client', 'key' => 'user_id', 'otherKey' => 'user_id'],
    ];

    public $attachMany = [
        'files' => ['System\Models\File'],
    ];

    public $morphMany = [
        'revision_history' => ['System\Models\Revision', 'name' => 'revisionable']
    ];

    /**
     * The attributes on which the post list can be ordered
     * @var array
     */
    public static $allowedSortingOptions = array(
        'name asc' => 'Name (ascending)',
        'name desc' => 'Name (descending)',
        'created_at asc' => 'Posted date (ascending)',
        'created_at desc' => 'Posted date (descending)',
        'updated_at asc' => 'Last updated (ascending)',
        'updated_at desc' => 'Last updated (descending)',
    );

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value;
        $this->attributes['description_html'] = Markdown::parse(trim($value));
    }

    public function setInstructionsAttribute($value)
    {
        $this->attributes['instructions'] = $value;
        $this->attributes['instructions_html'] = Markdown::parse(trim($value));
    }

    public function beforeCreate()
    {
        if (!$this->status_id) {
            $this->status = Attribute::applyType(Attribute::PROJECT_STATUS)
                ->whereCode(self::STATUS_DRAFT)
                ->first();
        }
    }

    public function beforeValidate()
    {
        if (!$this->is_remote) {
            $this->rules['latitude'] = 'required';
            $this->rules['longitude'] = 'required';
        }
    }

    /**
     * Lists projects for the front end
     * @param  array $options Display options
     * @return self
     */
    public function scopeListFrontEnd($query, $options = [])
    {
        /*
         * Default options
         */
        extract(array_merge([
            'page'       => 1,
            'perPage'    => 30,
            'sort'       => 'created_at',
            'skills'     => null,
            'search'     => '',
            'visible'    => true
        ], $options));

        $searchableFields = ['name', 'slug', 'description'];

        if ($visible)
            $query->applyVisible();

        /*
         * Sorting
         */
        if (!is_array($sort)) $sort = [$sort];
        foreach ($sort as $_sort) {

            if (in_array($_sort, array_keys(self::$allowedSortingOptions))) {
                $parts = explode(' ', $_sort);
                if (count($parts) < 2) array_push($parts, 'desc');
                list($sortField, $sortDirection) = $parts;

                $query->orderBy($sortField, $sortDirection);
            }
        }

        /*
         * Search
         */
        $search = trim($search);
        if (strlen($search)) {
            $query->searchWhere($search, $searchableFields);
        }

        /*
         * Skills
         */
        if ($skills !== null) {
            if (!is_array($skills)) $skills = [$skills];
            $query->whereHas('skills', function($q) use ($skills) {
                $q->whereIn('id', $skills);
            });
        }

        return $query->paginate($perPage, $page);
    }

    public function getBackendUrl()
    {
        return Backend::url('ahoy/pyrolancer/projects/preview/'.$this->id);
    }

    public function getUrl()
    {
        return CmsPage::url('project', [
            'id' => $this->id,
            'slug' => $this->slug,
        ]);
    }

    public function getRevisionableUser()
    {
        if (App::runningInBackend()) {
            return BackendAuth::getUser();
        }
    }

    //
    // Scopes
    //

    public function scopeApplyVisible($query)
    {
        return $query->where('is_visible', true);
    }

    //
    // Helpers
    //

    /**
     * Can the user bid on this project
     */
    public function canBid($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        if ($this->isOwner($user))
            return false;

        return true;
    }

    /**
     * Checks if the supplied user has a bid, and if true returns it.
     */
    public function hasBid($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        $userBid = $this->bids->first(function($key, $bid) use ($user) {
            return $bid->user_id == $user->id;
        });

        return is_null($userBid) ? false : $userBid;
    }

    /**
     * Returns true if the project has unanswered messages.
     * @return bool
     */
    public function hasUnansweredMessages()
    {
        return $this->messages->filter(function($message) {
            return !$message->parent_id && !$message->getChildCount();
        })->count() > 0;
    }

    /**
     * Can new messages be posted to this project
     */
    public function canMessage($user = null)
    {
        if (!$user = $this->lookupUser($user))
            return false;

        return true;
    }

    /**
     * Can the user, or logged in user, edit this project.
     */
    public function canEdit($user = null)
    {
        return $this->isOwner($user);
    }

    //
    // Status workflow
    //

    public function markSubmitted()
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_PENDING);
    }

    public function markApproved()
    {
        $this->update(['is_visible' => true, 'is_approved' => true]);
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_ACTIVE);
    }

    public function markRejected($reason = null)
    {
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_REJECTED);
    }

    public function markSuspended()
    {
        $this->update(['is_visible' => false]);
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_SUSPENDED);
    }

}