<?php
/*
* File: HasEvents.php
* Category: -
* Author: M.Goldenbaum
* Created: 21.09.20 22:46
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Traits;


use Webklex\PHPIMAP\Events\Event;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;

/**
 * Trait HasEvents
 *
 * @package Webklex\PHPIMAP\Traits
 */
trait HasEvents {

    /**
     * Event holder
     *
     * @var array<string, array<string, string>> $events
     */
    protected $events = [];

    /**
     * Set a specific event
     */
    public function setEvent(string $section, string $event, string $class): void {
        if (isset($this->events[$section])) {
            $this->events[$section][$event] = $class;
        }
    }

    /**
     * Set all events
     * @param array<string, array<string, string>> $events
     */
    public function setEvents($events): void {
        foreach($events as $section => $sectionEvents) {
            assert(is_string($section));
            foreach($sectionEvents as $event => $class) {
                assert(is_string($event));
                assert(is_string($class) && is_a($class, Event::class, true));
            }
        }
        $this->events = $events;
    }

    /**
     * Get a specific event callback
     * @param $section
     * @param $event
     *
     * @return string
     * @throws EventNotFoundException
     */
    public function getEvent($section, $event): string {
        if (isset($this->events[$section])) {
            return $this->events[$section][$event];
        }
        throw new EventNotFoundException();
    }

    /**
     * Get all events
     *
     * @return array<string, array<string, string>>
     */
    public function getEvents(): array {
        return $this->events;
    }

}
