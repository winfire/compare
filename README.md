1、主要比较点有 :

（1）中文分词，页面标红的词，一般为分词结果；

（2）排序结果，或者简单理解就是搜索的准确性，本实现对准确性的比较依赖用户最终的选择反馈；

（3）响应时间（由于google实时搜索受限，本程序未实现）；

（4）推荐搜索比对(未实现)；

（5）商业化比重分析(未实现)；



2、文件说明：

（1）bootstrap目录,前端框架文件；

（2）files目录，百度、google对特定关键词的搜索结果文件；

（3）phpQuery目录，是一个基于PHP的开源项目,方便处理DOM文档内容；

（4）index.php，功能实现文件；



3、index.php文件主要函数说明：

（1）GetRedirectUrl($url, $referer = '', $timeout = 10)

功能：获取指定url的重定向url；

参数：url 需要指定重定向的url referer 来源referer ,timeout 超时设置

返回：成功 指定url的重定向url 失败 false

（2）curlGetHtml($url，$timeout = 25)

功能：以Get方式获取指定url的页面内容；

参数：url 请求的url ,timeout 超时设置

 返回：成功 html代码 失败 false

（3）PickupFromGoogle($filePath)

功能：从Google的搜索结果文件里面获取分词、链接信息

参数：filePath 结果路径，本项目固定是/files/google.htm

返回：分词、链接信息

（4）PickupFromBaidu($keyWord)

功能：百度实时搜索关键词，并从返回的html代码里面获取分词、链接信息

参数：keyWord 关键词

返回：分词、链接信息
