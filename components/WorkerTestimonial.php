<?php namespace Responsiv\Pyrolancer\Components;

use Mail;
use Config;
use Redirect;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\WorkerReview;
use Cms\Classes\ComponentBase;

class WorkerTestimonial extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Worker Testimonial',
            'description' => 'Allows workers to request testimonial reviews'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    //
    // Object properties
    //

    public function testimonial()
    {
        if (
            (!$id = $this->param('id')) ||
            (!$hash = $this->param('hash'))
        ) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, WorkerReview::where('invite_hash', $hash)->find($id));
    }

    public function onLoadTestimonialForm()
    {
        $this->page['from_email'] = Config::get('mail.from.address');
    }

    public function onRequestTestimonial()
    {
        if (!$worker = WorkerModel::getFromUser()) return;

        $review = WorkerReview::createTestimonial($worker, post('Testimonial'));

        $testimonialUrl = Page::url('worker/testimonial', [
            'id' => $review->id,
            'hash' => $review->invite_hash,
        ]);

        $params = [
            'site_name' => Theme::getActiveTheme()->site_name,
            'worker' => $worker->toArray(),
            'user' => $worker->user->toArray(),
            'review' => $review->toArray(),
            'url' => $testimonialUrl
        ];

        Mail::sendTo(post('Testimonial[invite_email]'), 'responsiv.pyrolancer::mail.worker-testimonial-request', $params);

        $this->page['success'] = true;
        $this->page['email'] = post('Testimonial[invite_email]');
    }

    public function onSubmitTestimonial()
    {
        if (!$testimonial = $this->testimonial()) return;

        $testimonial->completeTestimonial(post('Testimonial'));

        $params = [
            'site_name' => Theme::getActiveTheme()->site_name,
            'review' => $testimonial->toArray(),
            'url' => $testimonial->worker->url
        ];

        Mail::sendTo($testimonial->user, 'responsiv.pyrolancer::mail.worker-testimonial-complete', $params);

        return Redirect::refresh();
    }


}
