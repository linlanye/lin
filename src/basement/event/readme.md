额外功能：
Event::run()可自动加载一次所有事件注册文件，只可运行一次，若需多次调用则每次需调用Event::reset()
Event::reset()可重置并清空已有数据
Event::one($eventName,$callable)可注册一次性事件