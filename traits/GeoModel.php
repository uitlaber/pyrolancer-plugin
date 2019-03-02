<?php namespace Responsiv\Pyrolancer\Traits;

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

    /**
     * Get a center latitude, longitude from an array of geo points.
     *
     * @param array data 2 dimensional array of latitudes and longitudes
     * For Example:
     * $data = [
     *   0 => [45.849382, 76.322333],
     *   1 => [45.843543, 75.324143],
     *   2 => [45.765744, 76.543223],
     *   3 => [45.784234, 74.542335]
     * ];
    */
    public function makeAreaAverage(array $data)
    {
        $count = count($data);

        $x = 0.0;
        $y = 0.0;
        $z = 0.0;

        foreach ($data as $coord) {
            $lat = $coord[0] * pi() / 180;
            $lng = $coord[1] * pi() / 180;

            $a = cos($lat) * cos($lng);
            $b = cos($lat) * sin($lng);
            $c = sin($lat);

            $x += $a;
            $y += $b;
            $z += $c;
        }

        $x /= $count;
        $y /= $count;
        $z /= $count;

        $lng = atan2($y, $x);
        $hyp = sqrt($x * $x + $y * $y);
        $lat = atan2($z, $hyp);

        return [$lat * 180 / pi(), $lng * 180 / pi()];
    }

}
