<?php namespace Responsiv\Pyrolancer\Models;

use Model;
use Responsiv\Pyrolancer\Classes\EventLogCollection;

/**
 * A log of user events
 */
class UserEventLog extends Model
{
    use \Responsiv\Pyrolancer\Traits\ModelUtils;
    use \October\Rain\Database\Traits\SoftDelete;

    const TYPE_USER_CREATED = 'user-created';
    const TYPE_WORKER_CREATED = 'worker-created';
    const TYPE_PORTFOLIO_CREATED = 'portfolio-created';
    const TYPE_PROJECT_CREATED = 'project-created';
    const TYPE_PROJECT_MESSAGE = 'project-message';
    const TYPE_PROJECT_BID = 'project-bid';

    public $table = 'responsiv_pyrolancer_user_event_log';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
        'other_user' => 'RainLab\User\Models\User'
    ];

    public $morphTo = [
        'related' => []
    ];

    public static function add($type, $options = [])
    {
        extract(array_merge([
            'user' => null,
            'otherUser' => null,
            'createdAt' => null,
            'related' => null,
        ], $options));

        $obj = new self;
        $obj->user_id = $user->id;
        $obj->type = $type;

        if ($createdAt) {
            $obj->created_at = $createdAt;
        }

        if ($otherUser) {
            $obj->other_user_id = $otherUser->id;
        }

        if ($related) {
            $obj->related_type = get_class($related);
            $obj->related_id = $related->id;
        }

        $obj->save();

        return $obj;
    }

    public static function remove($type, $options = [])
    {
        extract(array_merge([
            'user' => null,
            'otherUser' => null,
            'createdAt' => null,
            'related' => null,
        ], $options));

        $filtersApplied = 0;
        $obj = self::where('type', $type);

        if ($related) {
            $obj = $obj
                ->where('related_id', $related->id)
                ->where('related_type', get_class($related))
            ;
            $filtersApplied++;
        }

        if ($filtersApplied > 0) {
            $obj->delete();
        }
    }

    /**
     * Log items related to the public.
     */
    public function scopeApplyPublic($query)
    {
        return $query->whereNotIn('type', [self::TYPE_USER_CREATED]);
    }

    public function scopeApplyPrivate($query, $user = null)
    {
        if (!$user = $this->lookupUser($user)) {
            return $query->whereRaw('1 = 2');
        }

        return $query->where(function($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('other_user_id', $user->id);
        });
    }

    /**
     * Applies general eager loads.
     */
    public function scopeApplyEagerLoads($query)
    {
        return $query
            ->with('user.worker.skills')
            ->with('user.worker.logo')
            ->with('user.client')
            ->with('user.avatar')
            ->with('related')
        ;
    }
}
