<?php
namespace Mapbender\GeoTransporterBundle\Events;

/**
 * Created by PhpStorm.
 * User: egert
 * Date: 14.03.16
 * Time: 10:23
 */
class Event extends \Symfony\Component\EventDispatcher\Event
{

    protected $data;

    /**
     * Event constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}