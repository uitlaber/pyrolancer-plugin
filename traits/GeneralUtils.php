<?php namespace Responsiv\Pyrolancer\Traits;

trait GeneralUtils
{
    // Decode Identifiers YouTube style
    protected function shortDecodeId($int)
    {
        return ((int) base_convert(str_rot13($int), 36, 10)) - 100;
    }

    // Encode Identifiers YouTube style
    protected function shortEncodeId($int)
    {
        return str_rot13(base_convert((int) $int + 100, 10, 36));
    }
}
