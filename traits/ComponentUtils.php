<?php namespace Responsiv\Pyrolancer\Traits;

trait ComponentUtils
{
    protected $modelLookupCache = [];

    protected function lookupModel($class)
    {
        if (!is_string($class))
            $class = get_class($class);

        if ($model = array_get($this->modelLookupCache, $class))
            return $model;

        if (!$slug = $this->propertyOrParam('idParam'))
            return null;

        return $this->modelLookupCache[$class] = $class::whereSlug($slug)->first();
    }
}