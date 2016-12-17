<?php
namespace Ad5001\Git2Plugin\events;

use pocketmine\event\plugin\PluginEvent;
/*
Called when a plugin is updating
*/
class PluginUpdateEvent extends PluginEvent {

    /*
    Constructs the class
    @param     $newPlugin   \pocketmine\plugin\Plugin
    */
    public function __construct(\pocketmine\plugin\Plugin $newPlugin) {
        parent::__construct($newPlugin);
    } 

}