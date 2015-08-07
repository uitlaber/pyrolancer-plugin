<?php namespace Ahoy\Pyrolancer\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ahoy_pyrolancer_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->auto_approve_projects = false;
    }
}