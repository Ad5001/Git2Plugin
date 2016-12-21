<?php
namespace Ad5001\Git2Plugin\events;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\event\Cancellable;
/*
Called just before a plugin is updating
*/
class PluginPreUpdateEvent extends PluginEvent implements Cancellable {

    static $handlerList = null;

    /*
    Constructs the class
    @param     $oldPlugin   \pocketmine\plugin\Plugin|null
    @param     $desc   \pocketmine\plugin\PluginDescription
    @param     $oldPluginFiles   array
    */
    public function __construct($oldPlugin, \pocketmine\plugin\PluginDescription $desc, array $oldPluginFiles) {
        $this->plugin = $oldPlugin;
        $this->newDescription = $desc;
        $this->files = $oldPluginFiles;
    } 


    /*
    Returns the description.
    @return  \pocketmine\plugin\PluginDescription
    */
    public function getDescription() {
        return $this->desc;
    }


    /*
    Return all the files that will be deleted if updating (old plugin binaries)
    @return string[]
    */
    public function getFiles() {
        return $this->oldPluginFiles;
    }

}