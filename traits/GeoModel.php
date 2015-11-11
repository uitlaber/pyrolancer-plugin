<?php namespace Ahoy\Pyrolancer\Traits;

use Db;

trait GeoModel
{

    public function scopeApplyArea($query, $lat, $lng, $radius = null, $type = null)
    {
        /*
         * Defaults
         */
        if ($radius == null) {
            $radius = 100;
        }

        if ($type == null) {
            $type = 'km';
        }

        /*
         * Maximum 1000, self imposed limit
         */
        if (!floatval($radius) || floatval($radius) > 1000) {
            $radius = 1000;
        }

        $unit = $type == 'km'
            ? 6371 // kms
            : 3959; // miles

        $queryArr = [];
        $queryArr[] = sprintf('( %s * acos(', Db::getPdo()->quote($unit));
        $queryArr[] = sprintf('cos( radians( %s ) )', Db::getPdo()->quote($lat));
        $queryArr[] = '* cos( radians( latitude ) )';
        $queryArr[] = sprintf('* cos( radians( longitude ) - radians( %s ) )', Db::getPdo()->quote($lng));
        $queryArr[] = sprintf('+ sin( radians( %s ) )', Db::getPdo()->quote($lat));
        $queryArr[] = '* sin( radians( latitude ) )';
        $queryArr[] = sprintf(') ) < %s', Db::getPdo()->quote($radius));

        $query->whereRaw(implode('', $queryArr));
        return $query;
    }

}
