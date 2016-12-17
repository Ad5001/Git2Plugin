<?php


namespace Ad5001\Git2Plugin;

use pocketmine\command\CommandSender;

use pocketmine\command\Command;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Utils;

use pocketmine\Server;

use pocketmine\Player;

use Ad5001\Git2Plugin\tasks\PluginRefreshTask;






class Main extends PluginBase implements Listener {




   public function onEnable(){

        $this->reloadConfig();
        @mkdir($this->getDataFolder() . "tmp");
        @mkdir($this->getDataFolder() . "tmp/new");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        if(!($this->getServer()->getPluginManager()->getPlugin("Gitable") instanceof PluginBase)) {
            $this->getLogger()->notice("Gitable plugin not found. Downloading dependencies...");
            $plugin = Utils::getURL("http://downloads.ad5001.ga/plugins/Gitable.phar");
            file_put_contents($this->main->getServer()->getPluginPath() . "Gitable.phar", $plugin);
            $loader = new \pocketmine\plugin\PharPluginLoader($this->main->getServer());
            $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . "Gitable.phar");
            $loader->enablePlugin($pl);
        } else {
            $this->getLogger()->notice("Found Gitable v" . $this->getServer()->getPluginManager()->getPlugin("Gitable")->getDescription()->getVersion());
        }


        $this->getServer()->getScheduler()->scheduleRepeatingTask(new PluginRefreshTask($this), 20*60*$this->getConfig()->get("refresh_time"));


    }




    public function onLoad(){
        $this->saveDefaultConfig();
    }



    /*
    Deletes old plugin, add the new one, loads it.
    @param     $pluginpath    string
    @return string[]
    */
    public function getPluginFiles(string $pluginpath) {
        if(!is_dir($pluginpath)) {
            return [];
        }
        $plfiles = [];
        $pluginname = yaml_parse(strtolower(file_get_contents($pluginpath . "/plugin.yml")))["name"];
        foreach (array_diff(scandir($this->getServer()->getFilePath() . "plugins/"), [".", "..", ".git"]) as $path) {
            if(strpos(strtolower($path), $pluginname) !== false) {
                $plfiles[] = $path;
            }
        }
        return $plfiles;
    }


}