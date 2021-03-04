<?php

namespace NZTA\LeafletField\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

class LeafletField extends FormField
{

    /**
     * @var array
     */
    private static $map_options = [];
    /**
     * @var array
     */
    private static $draw_options = [];
    /**
     * @var DataObject
     */
    protected $data;
    /**
     * @var FormField
     */
    protected $geometryField;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $geoJsonlayers = [];

    /**
     * @var array
     */
    protected $geoJsonlayersStyle = [];

    /**
     * @param string $name The name of the field
     * @param string $title The title of the field
     * @param DataObject $data
     */
    public function __construct($name, $title = null, DataObject $data)
    {
        $this->data = $data;

        // setup the option defaults
        $this->options = [
            'map'  => $this->config()->map_options,
            'draw' => $this->config()->draw_options,
        ];

        $this->setupChildren($name);

        parent::__construct($name, $title, $data->$name);
    }

    /**
     * Set up child hidden fields.
     * @param string name
     *
     * @return FieldList
     */
    public function setupChildren($name)
    {
        $this->geometryField = HiddenField::create(
            $name . '[Geometry]',
            'Geometry',
            $this->data->$name
        )->addExtraClass('leafletfield-geometry');

        $this->children = new FieldList(
            $this->geometryField
        );

        return $this->children;
    }

    /**
     * @param array $properties
     *
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        // set the html js attributes
        $this->setAttribute('data-map-options', $this->getMapOptionsJS());
        $this->setAttribute('data-draw-options', $this->getDrawOptionsJS());
        $this->setAttribute('data-map-layers', $this->getGeoJsonlayersJS());
        $this->setAttribute('data-map-layers-style', $this->getGeoJsonlayersStyleJS());

        // set the dependencies
        $this->requireDependencies();

        return parent::Field($properties);
    }

    /**
     * Return the map options as a json string.
     *
     * @return String
     */
    public function getMapOptionsJS()
    {
        return Convert::array2json($this->getMapOptions());
    }

    /**
     * Return the L.map options.
     *
     * @return array
     */
    public function getMapOptions()
    {
        return $this->options['map'];
    }

    /**
     * Return the draw options as a json string.
     *
     * @return string
     */
    public function getDrawOptionsJS()
    {
        return Convert::array2json($this->getDrawOptions());
    }

    /**
     * Return the L.Control.Draw options.
     *
     * @return array
     */
    public function getDrawOptions()
    {
        return $this->options['draw'];
    }

    /**
     * Return additional geoJsonlayers to display on the map.
     *
     * @return string
     */
    public function getGeoJsonlayersJS()
    {
        return Convert::array2json($this->geoJsonlayers);
    }

    /**
     * Return additional geoJsonlayers style to display on the map.
     *
     * @return string
     */
    public function getGeoJsonlayersStyleJS()
    {
        return Convert::array2json($this->geoJsonlayersStyle);
    }

    protected function requireDependencies()
    {
        Requirements::javascript('//cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.3/leaflet.js');
        Requirements::javascript('//cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.4.9/leaflet.draw.js');
        Requirements::javascript('//maps.googleapis.com/maps/api/js?key=' . Environment::getEnv('GOOGLE_MAP_API_KEY'));
        Requirements::javascript('//unpkg.com/leaflet.gridlayer.googlemutant@latest/Leaflet.GoogleMutant.js');
        Requirements::javascript('nzta/googlemap-leafletfield: client/javascript/LeafletField.js');

        Requirements::css('//cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.3/leaflet.css');
        Requirements::css('//cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.4.9/leaflet.draw.css');
        Requirements::css('nzta/googlemap-leafletfield: client/css/LeafletField.css');
    }

    /**
     * @param array $value
     * @param $data
     *
     * @return $this|FormField
     */
    public function setValue($value, $data = null)
    {
        if (is_array($value) && isset($value['Geometry'])) {
            $this->geometryField->setValue($value['Geometry']);
        } elseif (is_string($value)) {
            $this->geometryField->setValue($value);
        }

        return $this;
    }

    /**
     * @param DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        if ($this->name) {
            $record->setCastedField($this->name, $this->geometryField->dataValue());
        }
    }

    /**
     * @return FieldList
     */
    public function getChildFields()
    {
        return $this->children;
    }

    /**
     * @return string
     */
    public function getGeometry()
    {
        $fieldName = $this->getName();
        return $this->data->$fieldName;
    }

    /**
     * Set the draw options, will override the config defaults.
     *
     * @param array $options
     */
    public function setDrawOptions($options = [])
    {
        $this->options['draw'] = array_merge($this->options['draw'], $options);
    }

    /**
     * Set the limit of layers that can be added
     *
     * @param integer $limit
     */
    public function setLimit($limit)
    {
        if (is_int($limit)) {
            $this->setMapOptions(['layerLimit' => $limit]);
        }
    }

    /**
     * Set the map options, will override the config defaults.
     *
     * @param array $options
     */
    public function setMapOptions($options = [])
    {
        $this->options['map'] = array_merge($this->options['map'], $options);
    }

    /**
     * Set the additional geoJson layers (readonly)
     *
     * @param array $geoJsonlayers
     */
    public function setGeoJsonLayers($geoJsonlayers)
    {
        $this->geoJsonlayers = $geoJsonlayers;
    }

    /**
     * Set the additional geoJson layers style (readonly)
     *
     * @param array $geoJsonlayersStyle
     */
    public function setGeoJsonLayersStyle($geoJsonlayersStyle)
    {
        $this->geoJsonlayersStyle = $geoJsonlayersStyle;
    }
}
