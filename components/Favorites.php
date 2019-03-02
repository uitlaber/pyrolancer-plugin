<?php namespace Responsiv\Pyrolancer\Components;

use Auth;
use Redirect;
use Cms\Classes\ComponentBase;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;
use Responsiv\Pyrolancer\Models\Favorite as FavoriteModel;
use ApplicationException;

class Favorites extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

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

    public function isPublic()
    {
        return !!$this->param('key');
    }

    public function isOwner()
    {
        return !$this->isPublic() && $this->hasList();
    }

    public function hasList()
    {
        return !!($list = $this->favoriteList()) && !!$list->workers->count();
    }

    public function favoriteList()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return ($listKey = $this->param('key'))
                ? FavoriteModel::listFromKey($listKey)
                : FavoriteModel::listFromUser(Auth::getUser());
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
            throw new ApplicationException('Action failed!');
        }

        $list = $this->findOrFirstFavoriteList();

        if ($list->workers->contains($worker)) {
            $list->workers()->remove($worker);
            $isFavorited = 0;
        }
        else {
            $list->workers()->add($worker);
            $isFavorited = 1;
        }

        $this->page['isFavorited'] = $isFavorited;
        $this->page['worker'] = $worker;
        $this->page['mode'] = 'view';
    }

    public function onEmptyList()
    {
        if (!$list = $this->favoriteList()) {
            return false;
        }

        // Empty the list
        $list->workers()->sync([]);

        return Redirect::refresh();
    }

    protected function findOrFirstFavoriteList()
    {
        return $this->favoriteList() ?: FavoriteModel::createList(Auth::getUser());
    }

}