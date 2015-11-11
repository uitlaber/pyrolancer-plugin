<?php namespace Ahoy\Pyrolancer\Models;

use Db;
use App;
use Auth;
use Model;
use Backend;
use BackendAuth;
use Cms\Classes\Page as CmsPage;
use Carbon\Carbon;
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
    const STATUS_DECLINED = 'declined';
    const STATUS_DEVELOPMENT = 'development';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

    use \Ahoy\Traits\ModelUtils;
    use \Ahoy\Pyrolancer\Traits\GeoModel;
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
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['expires_at', 'chosen_at', 'closed_at'];

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

    public $hasOne = [
        'review' => 'Ahoy\Pyrolancer\Models\WorkerReview',
    ];

    public $hasMany = [
        'bids'             => ['Ahoy\Pyrolancer\Models\ProjectBid', 'order' => 'total_estimate'],
        'messages'         => ['Ahoy\Pyrolancer\Models\ProjectMessage', 'conditions' => 'is_public = 1'],
        'private_messages' => ['Ahoy\Pyrolancer\Models\ProjectMessage', 'conditions' => 'is_public = 0', 'order' => 'created_at desc'],
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
        'chosen_user'      => ['RainLab\User\Models\User'],
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
            'latitude'   => null,
            'longitude'  => null,
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

        /*
         * Location
         */
        if ($latitude !== null && $longitude != null) {
            $query->where(function($q) use ($latitude, $longitude) {
                $q->applyArea($latitude, $longitude);
                $q->orWhere('is_remote', true);
            });
        }

        return $query->paginate($perPage, $page);
    }



    public function scopeApplyStatus($query, $codes)
    {
        $statuses = Attribute::listCodes(Attribute::PROJECT_STATUS);
        $statusIds = array_only($statuses, (array) $codes);
        return $query->whereIn('status_id', $statusIds);
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
        if (!$this->project_type_id) {
            return;
        }

        /*
         * Advert
         */
        if ($this->project_type->code == 'advert') {
            $this->count_applicants = $this->applicants->count();
        }
        /*
         * Auction
         */
        else {
            $this->count_bids = $this->bids->count();

            $totalBids = $this->count_bids ?: 1;
            $totalAmount = 0;
            foreach ($this->bids as $bid) {
                $totalAmount += $bid->total_estimate;
            }

            $this->average_bid = $totalAmount / $totalBids;
        }

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

    public function getLastStatusMessageAttribute()
    {
        if (!$lastStatus = $this->status_log->first()) {
            return null;
        }

        return array_get($lastStatus->data, 'message_html');
    }

    /**
     * Allowed to repick from this datetime onwards.
     */
    public function getRepickAtAttribute()
    {
        $hours = 24;
        return $this->chosen_at->addHours($hours);
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
     * Can new messages be posted to this project
     */
    public function canMessage($user = null)
    {
        if ($this->isOwner($user)) {
            return false;
        }

        if ($this->hasFinished()) {
            return false;
        }

        return true;
    }

    /**
     * Can the user bid on this project
     */
    public function canBid($user = null)
    {
        if ($this->isOwner($user)) {
            return false;
        }

        if ($this->hasChosenBid($user)) {
            return false;
        }

        if ($this->hasFinished()) {
            return false;
        }

        return true;
    }

    /**
     * Can the client repick another worker.
     */
    public function canRepick()
    {
        return Carbon::now()->gt($this->repick_at);
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
     * Checks if supplied user has bid, and it is chosen by the client.
     */
    public function hasChosenBid($user = null)
    {
        if (!$bid = $this->hasBid($user)) {
            return false;
        }

        return $this->chosen_bid_id == $bid->id ? $bid : false;
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
            self::STATUS_DEVELOPMENT,
            self::STATUS_TERMINATED,
            self::STATUS_COMPLETED,
            self::STATUS_CLOSED,
        ]);
    }

    public function hasActiveStatus()
    {
        return in_array($this->status->code, [
            self::STATUS_ACTIVE,
            self::STATUS_WAIT,
            self::STATUS_DECLINED,
        ]);
    }

    //
    // Status workflow
    //

    protected function markStatus($code, $data = null)
    {
        ProjectStatusLog::updateProjectStatus($this, $code, $data);
    }

    public function markSubmitted($reason = null)
    {
        $this->markStatus(self::STATUS_PENDING, [
            'message_md' => $reason
        ]);
    }

    public function markApproved()
    {
        $this->is_approved = true;
        $this->is_visible = true;
        $this->save();

        $this->markStatus(self::STATUS_ACTIVE);
    }

    public function markRejected($reason = null)
    {
        $this->markStatus(self::STATUS_REJECTED, [
            'message_md' => $reason
        ]);
    }

    public function markSuspended()
    {
        $this->is_visible = false;
        $this->save();

        $this->markStatus(self::STATUS_SUSPENDED);
    }

    public function markCancelled()
    {
        $this->is_visible = false;
        $this->save();

        $this->markStatus(self::STATUS_CANCELLED);
    }

    /**
     * A worker has been selected, waiting for confirmation.
     */
    public function markAccepted($bid = null)
    {
        $this->bids()
            ->where('is_chosen', true)
            ->update(['is_chosen' => false])
        ;

        if ($bid === null) {
            $this->chosen_user_id = null;
            $this->chosen_bid_id = null;
            $this->chosen_at = null;
            $this->save();

            $this->markStatus(self::STATUS_ACTIVE);
        }
        else {
            $this->chosen_user_id = $bid->user_id;
            $this->chosen_bid_id = $bid->id;
            $this->chosen_at = $this->freshTimestamp();
            $this->save();

            $bid->is_chosen = true;
            $bid->save();

            $this->markStatus(self::STATUS_WAIT);
        }
    }

    /**
     * The selected worker has declined the job.
     */
    public function markDeclined($reason = null)
    {
        $this->markStatus(self::STATUS_DECLINED, [
            'message_md' => $reason
        ]);
    }

    /**
     * The selected worker has accepted the job.
     */
    public function markDevelopment()
    {
        $this->markStatus(self::STATUS_DEVELOPMENT);
    }

    public function markTerminated($reason = null, $closedBy = 'system')
    {
        $this->closed_at = $this->freshTimestamp();
        $this->save();

        $this->markStatus(self::STATUS_TERMINATED, [
            'message_md' => $reason,
            'closed_by' => $closedBy
        ]);
    }

    public function markCompleted($closedBy = 'system')
    {
        $this->closed_at = $this->freshTimestamp();
        $this->save();

        $this->markStatus(self::STATUS_COMPLETED, [
            'closed_by' => $closedBy
        ]);
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

    //
    // Reviews
    //

    public function hasReview($user = null)
    {
        if (!$user = $this->lookupUser($user)) {
            return false;
        }

        if (!$this->review) {
            return false;
        }

        if ($this->isOwner($user)) {
            return $this->review->is_visible;
        }
        else {
            return $this->review->client_is_visible;
        }
    }

}