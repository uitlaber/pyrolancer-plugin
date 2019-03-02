<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use RainLab\User\Models\MailBlocker;
use ApplicationException;

class Account extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Account',
            'description' => 'Account management for all users'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Returns the logged in user, if available
     */
    public function user()
    {
        return $this->lookupObject(__FUNCTION__, Auth::getUser());
    }

    public function onPatch()
    {
        $user = $this->lookupUser();
        $data = $this->patchModel($user);
        $user->save();

        $this->page['user'] = $user;

        /*
         * Password has changed, reauthenticate the user
         */
        if (array_get($data, 'password')) {
            Auth::login($user->reload(), true);
        }
    }

    //
    // Notifications
    //

    public function notificationBlocks()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            $templates = MailBlocker::checkAllForUser($this->lookupUser());
            return array_build($templates, function($key, $value) {
                return [str_replace('responsiv.pyrolancer::mail.', '', $value), $key];
            });
        });
    }

    public function isNotificationBlockAll()
    {
        return MailBlocker::isBlockAll($this->lookupUser());
    }

    public function isNotificationBlocked($template)
    {
        $userBlocks = $this->notificationBlocks();
        $templates = explode('|', $template);
        foreach ($templates as $_template) {
            if (isset($userBlocks[$_template])) {
                return true;
            }
        }

        return false;
    }

    public function onUpdateNotifications()
    {
        $user = $this->lookupUser();
        $blockAll = post('Notification[all]', false);

        if ($blockAll) {
            MailBlocker::blockAll($user);
        }
        else {
            $templates = array_except((array) post('Notification'), 'all');

            // Break open multi templates
            foreach ($templates as $template => $value) {
                if (strpos($template, '|') === false) continue;
                unset($templates[$template]);
                $parts = explode('|', $template);
                foreach ($parts as $part) {
                    $templates[$part] = $value;
                }
            }

            $templates = array_build($templates, function($key, $value) {
                return ['responsiv.pyrolancer::mail.'.$key, $value];
            });

            MailBlocker::unblockAll($user);
            MailBlocker::setPreferences($user, $templates, ['verify' => true]);
        }
    }

}
