<?php namespace Responsiv\Pyrolancer\Classes;

use Session;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;

/**
 * A deferred object for holding project data
 */

class ProjectData
{

    const SESSION_NAME = 'pyrolancer.project';

    public static function getProjectObject()
    {
        $data = self::load();
        if (empty($data))
            return null;

        $project = new ProjectModel($data);
        return $project;
    }

    public static function saveProjectData()
    {
        $data = self::load();
        $data = array_merge($data, input('Project'), []);
        self::save($data);
    }

    public static function saveSessionKey()
    {
        $data = self::load();
        $data['session_key'] = input('session_key');
        self::save($data);
    }

    public static function getSessionKey()
    {
        $data = self::load();
        if (isset($data['session_key']))
            return $data['session_key'];

        return null;
    }

    public static function reset()
    {
        Session::put(self::SESSION_NAME, []);
    }

    public static function load()
    {
        return Session::get(self::SESSION_NAME, []);
    }

    public static function save($data)
    {
        Session::put(self::SESSION_NAME, $data);
    }

}