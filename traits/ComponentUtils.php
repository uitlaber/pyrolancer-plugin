<?php namespace Responsiv\Pyrolancer\Traits;

use Auth;

trait ComponentUtils
{
    protected $modelLookupCache = [];
    protected $modelLookupSecureCache = [];

    protected function lookupUser()
    {
        if (!$user = Auth::getUser())
            throw new ApplicationException('You must be logged in');

        return $user;
    }

    protected function lookupModel($class, $scope = null)
    {
        if (is_string($class)) {
            $query = new $class;
        }
        else {
            $query = $class;
            $class = get_class($class);
        }

        if ($model = array_get($this->modelLookupCache, $class))
            return $model;

        if (!$slug = $this->property('slug'))
            return null;

        if (is_callable($scope))
            $scope($query);

        return $this->modelLookupCache[$class] = $query->whereSlug($slug)->first();
    }

    protected function lookupModelSecure($class, $user = null, $scope = null)
    {
        if (is_string($class)) {
            $query = new $class;
        }
        else {
            $query = $class;
            $class = get_class($class);
        }

        if ($model = array_get($this->modelLookupSecureCache, $class))
            return $model;

        if (!$id = post('id'))
            return null;

        if (is_callable($scope))
            $scope($query);

        if (!$model = $query->find($id))
            return null;

        if (!$model->canEdit($user))
            return false;

        return $this->modelLookupSecureCache[$class] = $model;
    }

}