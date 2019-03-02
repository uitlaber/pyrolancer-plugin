<?php namespace Responsiv\Pyrolancer\Traits;

use Auth;
use Mail;
use Validator;
use ValidationException;
use ApplicationException;

/**
 * Reusable function to contact a user from their profile
 */
trait ProfileContactComponent
{
    public function onLoadProfileContactForm()
    {
        $this->page['user'] = $this->getProfileContactUser();
    }

    public function onSubmitProfileContact()
    {
        if (!$fromUser = Auth::getUser()) {
            throw new ApplicationException('Must be signed in!');
        }

        if (!$user = $this->getProfileContactUser()) {
            throw new ApplicationException('Missing user!');
        }

        if (!is_array($data = post())) {
            throw new ApplicationException('Invalid data!');
        }

        $rules = ['comments' => 'required|min:5'];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $data['email'] = $fromUser->email;
        $data['name'] = $fromUser->name;

        Mail::sendTo($user, 'responsiv.pyrolancer::mail.profile-contact', $data, function($message) use ($data) {
            $message->replyTo($data['email'], $data['name']);
        });

        $this->page['user'] = $this->getProfileContactUser();
        $this->page['success'] = true;
    }
}
