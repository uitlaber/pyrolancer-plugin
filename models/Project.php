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
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_WAIT = 'wait';
    const STATUS_DEVELOPMENT = 'development';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

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
        'skills' => ['Ahoy\Pyrolancer\Models\Skill', 'table' => 'ahoy_pyrolancer_projects_skills', 'order' => 'name'],
        'skill_categories' => ['Ahoy\Pyrolancer\Models\SkillCategory', 'table' => 'ahoy_pyrolancer_projects_skill_categories', 'order' => 'name', 'otherKey' => 'category_id'],
        'applicants' => ['RainLab\User\Models\User', 'table' => 'ahoy_pyrolancer_projects_applicants', 'timestamps' => true],
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
        'chosen_bid'       => ['Ahoy\Pyrolancer\Models\ProjectBid'],
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
        'created_at desc' => 'Posted date (descending)',
        'created_at asc' => 'Posted date (ascending)',
        'updated_at desc' => 'Last updated (descending)',
        'updated_at asc' => 'Last updated (ascending)',
        'name desc' => 'Name (descending)',
        'name asc' => 'Name (ascending)',
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

    public function afterCreate()
    {
        $this->syncSkillCategories();
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
            'sort'       => 'created_at desc',
            'types'      => null,
            'positions'  => null,
            'skills'     => null,
            'categories' => null,
            'search'     => ''
        ], $options));

        $searchableFields = ['name', 'slug', 'description'];

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
         * Type & Position
         */
        if ($types) {
            $query->whereIn('project_type_id', (array) $types);
        }

        if ($positions) {
            $query->whereIn('position_type_id', (array) $positions);
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

        /*
         * Skills categories
         */
        if ($categories !== null) {
            if (!is_array($categories)) $categories = [$categories];
            $query->whereHas('skill_categories', function($q) use ($categories) {
                $q->whereIn('id', $categories);
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

    /**
     * Rebuilds the statistics for the channel
     * @return void
     */
    public function rebuildStats()
    {
        $this->count_bids = $this->bids->count();

        $totalBids = $this->count_bids ?: 1;
        $totalAmount = 0;
        foreach ($this->bids as $bid) {
            $totalAmount += $bid->getTotalEstimate();
        }

        $this->average_bid = $totalAmount / $totalBids;
        return $this;
    }

    /**
     * Reassigns the skill categories for this project, based on the 
     * specified skills.
     */
    public function syncSkillCategories()
    {
        if (!$this->skills) {
            return;
        }

        $categoryIds = [];
        foreach ($this->skills as $skill) {
            $categoryId = $skill->category_id;
            if ($categoryId && !isset($categoryIds[$categoryId])) {
                $categoryIds[$categoryId] = $categoryId;
            }
        }

        if (count($categoryIds)) {
            $this->skill_categories()->sync($categoryIds);
        }
    }

    //
    // Attributes
    //

    public function getVisibleBidsAttribute()
    {
        return $this->bids->filter(function($bid) {
            return !$bid->is_hidden;
        });
    }

    public function getHiddenBidsAttribute()
    {
        return $this->bids->filter(function($bid) {
            return $bid->is_hidden;
        });
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
        if (!$user = $this->lookupUser($user)) {
            return false;
        }

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
        if (!$user = $this->lookupUser($user)) {
            return false;
        }

        return true;
    }

    /**
     * Can the user, or logged in user, edit this project.
     */
    public function canEdit($user = null)
    {
        return $this->isOwner($user) && !$this->hasFinished();
    }

    /**
     * The project has reached the end of the line, no further actions
     * can be made on these projects.
     */
    public function hasFinished()
    {
        return in_array($this->status->code, [
            self::STATUS_CANCELLED,
            self::STATUS_TERMINATED,
            self::STATUS_COMPLETED,
            self::STATUS_CLOSED,
        ]);
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
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_REJECTED, [
            'message_md' => $reason
        ]);
    }

    public function markSuspended()
    {
        $this->update(['is_visible' => false]);
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_SUSPENDED);
    }

    public function markCancelled()
    {
        $this->update(['is_visible' => false]);
        ProjectStatusLog::updateProjectStatus($this, self::STATUS_CANCELLED);
    }

    //
    // Advert
    //

    public function hasApplicant($user = null)
    {
        if (!$user = $this->lookupUser($user)) {
            return false;
        }

        $userApplicant = $this->applicants->first(function($key, $applicant) use ($user) {
            return $applicant->id == $user->id;
        });

        return is_null($userApplicant) ? false : $userApplicant;
    }

}