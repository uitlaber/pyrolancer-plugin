<?php namespace Responsiv\Pyrolancer\Traits;

trait ComponentUtils
{
    protected $modelLookupCache = [];

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

        if (!$slug = $this->propertyOrParam('idParam'))
            return null;

        if (is_callable($scope))
            $scope($query);

        return $this->modelLookupCache[$class] = $query->whereSlug($slug)->first();
    }
}