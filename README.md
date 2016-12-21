# Git2Plugin
Git2Plugin is a plugin that allows you to download plugins thought Git from many websites like Github.  
It also allows plugins to be build, runed, and updated at the same time.    
Git2Plugin takes three minutes to detect an update, and three more minutes to apply them.   
Git2Plugin is also dependent from Gitable.    
     

## How to add a source?
Go to any website (like github.com) that hosts git and take the git cloning url (for github: https://github.com/Author/Plugin.git)      
Then, open the config, and add to the "srcs" (or replace an existing one) the URL you just took.    
That's it ! You added a source !    
    
## API
Git2Plugin also have a small API allowing plugins to:
- Add a source (Main -> addSource(string $url))
- Check before an update (\Ad5001\Git2Plugin\events\PluginPreUpdateEvent, Cancellable) with the old plugin and all.
- Check after an update (\Ad5001\Git2Plugin\events\PluginPreUpdateEvent) with the freshly updated plugin.
If you have any other suggestion, please write an issue for it.