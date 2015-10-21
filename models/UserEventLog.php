<?php namespace Ahoy\Pyrolancer\Models;

use Model;

/**
 * A log of user events
 */
class UserEventLog extends Model
{
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

    public static function add($type, $user, $related = null, $otherUser = null)
    {
        $obj = new self;
        $obj->user_id = $user;
        $obj->type = $type;
        $obj->save();

        return $obj;
    }
}
