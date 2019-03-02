<?php namespace Responsiv\Pyrolancer\Components;

use Cms\Classes\ComponentBase;
use RainLab\Location\Models\State as StateModel;
use RainLab\Location\Models\Country as CountryModel;
use Responsiv\Pyrolancer\Models\Skill as SkillModel;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\Worker as WorkerModel;

class SeoDirectory extends ComponentBase
{
    use \Responsiv\Pyrolancer\Traits\ComponentUtils;

    protected $mode;

    public function componentDetails()
    {
        return [
            'name'        => 'SEO Directory',
            'description' => 'Used for search engines to find freelancers'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $mode = 'country';

        if ($this->states()) {
            $mode = 'state';

            if ($this->vicinities()) {
                $mode = 'vicinity';

                if ($this->skillCategories()) {
                    $mode = 'skill';

                    if ($this->workers()) {
                        $mode = 'directory';
                    }
                }
            }
        }

        $this->page['mode'] = $this->mode = $mode;
    }

    //
    // Helpers
    //

    public function makePageTitle($options)
    {
        $title = array_get($options, $this->mode, 'country');

        if (strpos($title, ':country') !== false && ($country = $this->country())) {
            $title = strtr($title, [':country' => $country->name]);
        }

        if (strpos($title, ':state') !== false && ($state = $this->state())) {
            $title = strtr($title, [
                ':stateCode' => $state->code,
                ':state' => $state->name
            ]);
        }

        if (strpos($title, ':vicinity') !== false && ($vicinity = $this->vicinity())) {
            $title = strtr($title, [':vicinity' => $vicinity->name]);
        }

        if (strpos($title, ':skill') !== false && ($skill = $this->skill())) {
            $title = strtr($title, [':skill' => $skill->name]);
        }

        return $title;
    }


    //
    // Country
    //

    public function countries()
    {
        return $this->lookupObject(__FUNCTION__, function() {
            return CountryModel::isEnabled()->get();
        });
    }

    public function countryCode()
    {
        return strtoupper($this->param('country', ''));
    }

    public function country()
    {
        if (!$this->countryCode()) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, function() {
            return CountryModel::isEnabled()
                ->where('code', $this->countryCode())
                ->first()
            ;
        });
    }

    //
    // State
    //

    public function states()
    {
        if ($country = $this->country()) {
            return $country->states;
        }

        return null;
    }

    public function stateCode()
    {
        return strtoupper($this->param('state', ''));
    }

    public function state()
    {
        if (!$this->stateCode() || !$this->country()) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, function() {
            return $this->country()
                ->states()
                ->where('code', $this->stateCode())
                ->first()
            ;
        });
    }

    //
    // Vicinities
    //

    public function vicinities()
    {
        if ($state = $this->state()) {
            return $state->vicinities()
                ->orderBy('count_workers', 'desc')
                ->get();
        }

        return null;
    }

    public function vicinityCode()
    {
        return strtoupper($this->param('vicinity', ''));
    }

    public function vicinity()
    {
        if (!$this->vicinityCode() || !$this->state()) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, function() {
            return $this->state()
                ->vicinities()
                ->where('slug', $this->vicinityCode())
                ->first()
            ;
        });
    }

    //
    // Skills
    //

    public function skillCategories()
    {
        if (!$this->vicinity()) {
            return null;
        }

        return $this->lookupObject(__FUNCTION__, function() {
            return SkillCategory::applyVisible()
                ->with(['skills' => function($query) {
                    $query->applyVisible()->whereHas('workers', function($query) {
                        $query->where('vicinity_id', $this->vicinity()->id);
                    });
                }])
                ->get()
            ;
        });
    }

    public function skillCode()
    {
        return strtoupper($this->param('skill', ''));
    }

    public function skill()
    {
        return SkillModel::where('slug', $this->skillCode())->first();
    }

    //
    // Workers
    //

    public function workers()
    {
        if (
            ($vicinity = $this->vicinity()) &&
            ($skill = $this->skill())
        ) {

            return $this->lookupObject(__FUNCTION__, function() use ($vicinity, $skill) {
                $options = [
                    'skills' => $skill->id,
                    'latitude' => $vicinity->latitude,
                    'longitude' => $vicinity->longitude,
                    'page' => input('page')
                ];

                return WorkerModel::applyVisible()->listFrontEnd($options);
            });

        }

        return null;
    }

}