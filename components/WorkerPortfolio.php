<?php namespace Responsiv\Pyrolancer\Components;

use Redirect;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\Portfolio as PortfolioModel;
use Responsiv\Pyrolancer\Models\PortfolioItem;

class WorkerPortfolio extends ComponentBase
{

    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Portfolio Manage Component',
            'description' => 'Management features for a worker portfolio'
        ];
    }

    public function defineProperties()
    {
        return [
            'setupPage' => [
                'title'       => 'Setup page',
                'description' => 'The user will be redirected to this page if no portfolio exists. Set to "none" to disable the redirect.',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }

    public function getSetupPageOptions()
    {
        return [''=>'- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function init()
    {
        if ($portfolio = $this->portfolio()) {
            $component = $this->addComponent(
                'Responsiv\Uploader\Components\FileUploader',
                'portfolioUploader',
                ['deferredBinding' => true]
            );
            $component->bindModel('uploaded_file', new PortfolioItem);
        }
    }

    public function onRun()
    {
        if (($setupPage = $this->property('setupPage')) && !$this->hasPortfolio()) {
            return Redirect::to($this->pageUrl($setupPage));
        }
    }

    //
    // Object properties
    //

    public function worker()
    {
        return $this->lookupObject(__FUNCTION__, WorkerModel::getFromUser());
    }

    public function portfolio()
    {
        return $this->lookupObject(__FUNCTION__, PortfolioModel::getFromWorker());
    }

    public function portfolioItems()
    {
        return $this->portfolio()->items;
    }

    public function hasPortfolio()
    {
        $portfolio = $this->portfolio();
        return $portfolio->hasPortfolio();
    }

    //
    // AJAX
    //

    public function onCreatePortfolio()
    {
        $this->onCreateItem();

        if ($portfolio = $this->portfolio()) {
            $portfolio->completePortfolio();
        }

        if ($redirectUrl = input('redirect')) {
            return Redirect::to($redirectUrl);
        }
    }

    public function onDeleteItem()
    {
        $item = PortfolioItem::find(post('id'));
        if (!$item->portfolio || !$item->portfolio->isOwner())
            return;

        $item->delete();
    }

    public function onManageItem()
    {
        return post('id')
            ? $this->onUpdateItem()
            : $this->onCreateItem();
    }

    public function onCreateItem()
    {
        $portfolio = $this->portfolio();

        $item = new PortfolioItem;
        $item->portfolio = $portfolio;
        $item->fill((array) post('PortfolioItem'));
        $item->save(null, post('_session_key'));

        $item->portfolio->touch();
    }

    public function onUpdateItem()
    {
        $item = PortfolioItem::find(post('id'));
        if (!$item->portfolio || !$item->portfolio->isOwner())
            return;

        $item->portfolio->checkPrimaryItem();

        $item->fill((array) post('PortfolioItem'));
        $item->save(null, post('_session_key'));
    }

    public function onLoadItemForm()
    {
        if (!$id = post('id')) return;

        $item = PortfolioItem::find($id);
        if (!$item->portfolio || !$item->portfolio->isOwner())
            return;

        $this->page['item'] = $item;
    }

}