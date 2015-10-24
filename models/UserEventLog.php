<?php namespace Ahoy\Pyrolancer\Models;

use Model;
use Ahoy\Pyrolancer\Classes\EventLogCollection;

/**
 * A log of user events
 */
class UserEventLog extends Model
{
    use \Ahoy\Traits\ModelUtils;

    const TYPE_USER_CREATED = 'user-created';
    const TYPE_WORKER_CREATED = 'worker-created';
    const TYPE_PORTFOLIO_CREATED = 'portfolio-created';
    const TYPE_PROJECT_CREATED = 'project-created';
    const TYPE_PROJECT_MESSAGE = 'project-message';
    const TYPE_PROJECT_BID = 'project-bid';

    public $table = 'ahoy_pyrolancer_user_event_log';

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

        if ($related) {
            $obj->related_type = get_class($related);
            $obj->related_id = $related->id;
        }

        $obj->save();

        return $obj;
    }

    /**
     * Log items related to the public.
     */
    public function scopeApplyVisible($query)
    {
        return $query->whereNotIn('type', [self::TYPE_USER_CREATED]);
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
