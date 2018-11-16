额外功能:
可动态加载，并可使用链式法则进行读写。如：
Config::configFile('key0.key1'),将读取configFile配置文件中的key0键下的key1键值
Config::configFile(['key0.key1'=>'value']),将设置configFile配置文件中的key0键下的key1键值
Config::clean(configName)，清空目标配置数据