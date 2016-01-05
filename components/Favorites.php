<?php namespace Ahoy\Pyrolancer\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Favorite as FavoriteModel;
use ApplcationException;

class Favorites extends ComponentBase
{
    use \Ahoy\Traits\ComponentUtils;

    public function componentDetails()
    {
        return [
            'name'        => 'Favorites Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function favoriteList()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return FavoriteModel::hasList(Auth::getUser());
        });
    }

    public function isFavorited($worker)
    {
        if (!$list = $this->favoriteList()) {
            return false;
        }

        return $list->workers->contains($worker);
    }

    public function onToggleFavorite()
    {
        if ((!$id = post('id')) || (!$worker = WorkerModel::find($id))) {
            throw new ApplcationException('Action failed!');
        }
    }

}