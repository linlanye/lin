额外功能：
Debug::run()输出内置系统信息和此前收集到到调试信息，并可显示这些信息，只可调用1次，若需多次调用，则每次需调用Debug::reset()方法
Debug::reset()可重置并清空已有数据
Debug::flag($flag)可自动匹配beginFlag()和endFlag()方法