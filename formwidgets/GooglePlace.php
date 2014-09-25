<?php namespace Responsiv\Pyrolancer\FormWidgets;

use HTML;
use Backend\Classes\FormWidgetBase;

/**
 * Google Place
 * Renders a geocoded address field.
 *
 * Usage:
 *
 *   address:
 *       label: Address
 *       type: googleaddress
 *       fieldMap:
 *           latitude: latitude
 *           longitude: longitude
 *           city: city
 *           zip: zip
 *
 * @package responsiv\pyrolancer
 * @author Responsiv Internet
 */
class GooglePlace extends FormWidgetBase
{
    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'address';

    protected $fieldMap;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->fieldMap = $this->getConfig('fieldMap', []);
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('googleplace');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadData();
        $this->vars['field'] = $this->formField;
    }

    public function getFieldMapAttributes()
    {
        $widget = $this->controller->formGetWidget();
        $fields = $widget->getFields();
        $result = [];
        foreach ($this->fieldMap as $map => $fieldName) {

            if (!$field = array_get($fields, $fieldName))
                continue;

            $result['data-input-'.$map] = '#'.$field->getId();
        }

        return HTML::attributes($result);
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->addJs('http://maps.googleapis.com/maps/api/js?libraries=places&sensor=false');
        $this->addJs('js/location-autocomplete.js', 'core');
    }

}