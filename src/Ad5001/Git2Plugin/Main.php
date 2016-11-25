<?php


namespace Ad5001\Git2Plugin;


use pocketmine\command\CommandSender;


use pocketmine\command\Command;


use pocketmine\event\Listener;


use pocketmine\plugin\PluginBase;


use pocketmine\Server;


use pocketmine\Player;






class Main extends PluginBase implements Listener {




   public function onEnable(){

        $this->reloadConfig();
        @mkdir($this->getDataFolder() . "tmp");
        @mkdir($this->getDataFolder() . "tmp/new");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);


    }




    public function onLoad(){
        $this->saveDefaultConfig();
    }



    /*
    Deletes old plugin, add the new one, loads it.
    @param     $pluginpath    string
    */
    public function getPluginFiles(string $pluginpath) {
        if(!is_dir($pluginpath)) {
            return [];
        }
        $plfiles = [];
        $pluginname = yaml_decode(strtolower(file_get_contents($pluginpath . "/plugin.yml")))["name"];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->getServer()->getFilePath() . "plugins/", RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if(strpos(strtolower($path), $pluginname) !== false) {
                $plfiles[] = $path;
            }
        }
        return $plfiles;
    }


}