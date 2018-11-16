强化：
Requert->set()将直接作用于源数据，如$_GET,$_POST,$_FILES。

可传参来自定义请求类型，并可使用http已有的请求类型来模拟。
如模拟update请求，使用post提交，并附带参数request_type='update'，则自动解析为update请求，并释放模拟字段，转移原始请求的数据到模拟的数据

额外功能：
动态获取设置当前请求方法对参数。
如Request->id，Request->id=1。当前请求为POST，等效为$_POST['id']，$_POST['id']=1；
若为GET，等效为$_GET['id']，$_GET['id']=1

使用Request->getUpload()，获取上传文件参赛，也即$_FILES
使用Request->getRawMethod()，获得原始对请求类型，而非模拟的请求类型
