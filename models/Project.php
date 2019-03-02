<?php namespace Responsiv\Pyrolancer\Models;

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

    use \Cms\Traits\UrlMaker;
    use \Responsiv\Pyrolancer\Traits\ModelUtils;
    use \Responsiv\Pyrolancer\Traits\GeoModel;
    use \October\Rain\Database\Traits\Sluggable;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Revisionable;
    use \October\Rain\Database\Traits\SoftDelete;

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
    public $table = 'responsiv_pyrolancer_projects';

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
        'skills' => ['Responsiv\Pyrolancer\Models\Skill', 'table' => 'responsiv_pyrolancer_projects_skills', 'order' => 'name'],
        'skill_categories' => ['Responsiv\Pyrolancer\Models\SkillCategory', 'table' => 'responsiv_pyrolancer_projects_skill_categories', 'order' => 'name', 'otherKey' => 'category_id'],
        'applicants' => ['RainLab\User\Models\User', 'table' => 'responsiv_pyrolancer_projects_applicants', 'timestamps' => true],
    ];

    public $hasOne = [
        'review' => 'Responsiv\Pyrolancer\Models\WorkerReview',
    ];

    public $hasMany = [
        'bids'             => ['Responsiv\Pyrolancer\Models\ProjectBid', 'order' => 'total_estimate', 'delete' => true],
        'messages'         => ['Responsiv\Pyrolancer\Models\ProjectMessage', 'conditions' => 'is_public = 1', 'delete' => true],
        'private_messages' => ['Responsiv\Pyrolancer\Models\ProjectMessage', 'conditions' => 'is_public = 0', 'order' => 'created_at desc', 'delete' => true],
        'status_log'       => ['Responsiv\Pyrolancer\Models\ProjectStatusLog', 'order' => 'id desc', 'delete' => true],
    ];

    public $belongsTo = [
        'category'         => ['Responsiv\Pyrolancer\Models\ProjectCategory'],
        'status'           => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'project.status'"],
        'project_type'     => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'project.type'"],
        'position_type'    => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'position.type'"],
        'budget_type'      => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.type'"],
        'budget_fixed'     => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.fixed'"],
        'budget_hourly'    => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.hourly'"],
        'budget_timeframe' => ['Responsiv\Pyrolancer\Models\Attribute', 'conditions' => "type = 'budget.timeframe'"],
        'user'             => ['RainLab\User\Models\User'],
        'client'           => ['Responsiv\Pyrolancer\Models\Client', 'key' => 'user_id', 'otherKey' => 'user_id'],
        'chosen_bid'       => ['Responsiv\Pyrolancer\Models\ProjectBid'],
        'vicinity_obj'     => ['Responsiv\Pyrolancer\Models\Vicinity', 'key' => 'vicinity_id'],
        'chosen_user'      => ['RainLab\User\Models\User'],
    ];

    public $attachMany = [
        'files' => ['System\Models\File'],
    ];

    public $morphMany = [
        'event_log'        => ['Responsiv\Pyrolancer\Models\UserEventLog', 'name' => 'related', 'delete' => true],
        'revision_history' => ['System\Models\Revision', 'name' => 'revisionable', 'delete' => true]
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

    /**
     * @var string The component to use for generating URLs.
     */
    protected $urlComponentName = 'project';

    /**
     * @var string The property name to determine a primary component.
     */
    protected $urlComponentProperty = 'isPrimary';

    /**
     * Returns an array of values to use in URL generation.
     * @return @array
     */
    public function getUrlParams()
    {
        return [
            'id' => $this->user_id,
            'slug' => $this->slug,
        ];
    }

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

        if (!$this->latitude) {
            $this->latitude = null;
        }

        if (!$this->longitude) {
            $this->longitude = null;
        }

        if (!$this->duration) {
            $this->duration = 30;
        }

        // "I want to enter skills by hand"
        if ($this->category_id == '-1') {
            $this->category_id = null;
        }
    }

    public function getBackendUrl()
    {
        return Backend::url('responsiv/pyrolancer/projects/preview/'.$this->id);
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

    public function getIsNewAttribute()
    {
        return (bool) $this->freshTimestamp()->subDays(2)->lt($this->created_at);
    }

    public function getIsExpiringSoonAttribute()
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->freshTimestamp() > $this->expires_at->subDays(7);
    }

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
        return $query
            ->where('is_approved', 1)
            ->where('is_hidden', '<>', 1);
    }

    public function scopeApplyActive($query)
    {
        return $query
            ->where('is_active', 1)
            ->where('is_approved', 1)
        ;
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
            'users'      => null,
            'types'      => null,
            'positions'  => null,
            'skills'     => null,
            'categories' => null,
            'vicinities' => null,
            'countries'  => null,
            'latitude'   => null,
            'longitude'  => null,
            'isRemote'   => null,
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

                $query->orderBy('is_active', 'desc');
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
         * Users
         */
        if ($users !== null) {
            if (!is_array($users)) $users = [$users];
            $query->whereIn('user_id', $users);
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
         * Countries
         */
        if ($countries !== null) {
            if (!is_array($countries)) $countries = [$countries];
            $query->whereIn('country_id', $countries);
        }

        /*
         * Vicinities
         */
        if ($vicinities !== null) {
            if (!is_array($vicinities)) $vicinities = [$vicinities];
            $query->whereIn('vicinity_id', $vicinities);
        }

        /*
         * Remote jobs
         */
        if ($isRemote) {
            $query->where('is_remote', true);
        }

        /*
         * Location
         */
        if ($latitude !== null && $longitude != null) {
            $query->applyArea($latitude, $longitude);
        }

        return $query->paginate($perPage, $page);
    }

    public function scopeApplyStatus($query, $codes)
    {
        $statuses = Attribute::listCodes(Attribute::PROJECT_STATUS);
        $statusIds = array_only($statuses, (array) $codes);
        return $query->whereIn('status_id', $statusIds);
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

        $userBid = $this->bids->first(function($bid) use ($user) {
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
            self::STATUS_EXPIRED,
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
            'message' => $reason
        ]);
    }

    public function markApproved()
    {
        $this->is_approved = true;
        $this->is_active = true;
        $this->expires_at = $this->freshTimestamp()->addDays((int) $this->duration);
        $this->save();

        $this->markStatus(self::STATUS_ACTIVE);
    }

    public function markExtended()
    {
        if (!$this->is_approved) {
            return;
        }

        $this->is_active = true;
        $this->expires_at = $this->freshTimestamp()->addDays(30);
        $this->save();

        $this->markStatus(self::STATUS_ACTIVE);
    }

    public function markRejected($reason = null)
    {
        $this->markStatus(self::STATUS_REJECTED, [
            'message' => $reason
        ]);
    }

    public function markSuspended()
    {
        $this->is_active = false;
        $this->save();

        $this->markStatus(self::STATUS_SUSPENDED);
    }

    public function markCancelled()
    {
        $this->is_active = false;
        $this->save();

        $this->markStatus(self::STATUS_CANCELLED);
    }

    public function markExpired()
    {
        $this->is_active = false;
        $this->expires_at = $this->freshTimestamp();
        $this->save();

        $this->markStatus(self::STATUS_EXPIRED);
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
            'message' => $reason
        ]);
    }

    /**
     * The selected worker has accepted the job.
     */
    public function markDevelopment()
    {
        $this->is_active = false;
        $this->save();

        $this->markStatus(self::STATUS_DEVELOPMENT);
    }

    public function markTerminated($reason = null, $closedBy = 'system')
    {
        $this->is_active = false;
        $this->closed_at = $this->freshTimestamp();
        $this->save();

        $this->markStatus(self::STATUS_TERMINATED, [
            'message' => $reason,
            'closed_by' => $closedBy
        ]);
    }

    public function markCompleted($closedBy = 'system')
    {
        $this->is_active = false;
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

        $userApplicant = $this->applicants->first(function($applicant) use ($user) {
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
