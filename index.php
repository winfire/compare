
<!DOCTYPE html>
<html lang="en-US">
    <head>     
        <title>搜索比对</title>    
        <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    </head>
    <body>      
        <div class="container-fluid">             
            <?php

            /**
             * GetRedirectUrl() 
             * 取重定向的地址 
             * @param string $url
             * @return string
             * */
            function GetRedirectUrl($url, $referer = '', $timeout = 10) {
                return FALSE;
                $redirect_url = false;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_PROXY, "http://dev-proxy.oa.com:8080");  //配置代理 
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过ssl检查项。
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_NOBODY, TRUE); //不返回请求体内容
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); //允许请求的链接跳转
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: */\*',
                    'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
                    'Connection: Keep-Alive'));
                if ($referer) {
                    curl_setopt($ch, CURLOPT_REFERER, $referer); //构造来路
                }
                $content = curl_exec($ch);
                if (!curl_errno($ch)) {
                    $redirect_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); //获取最终请求的url地址
                }
                return $redirect_url;
            }

            /**
             * 远程访问
             * @param type $url
             * @return 成功 html 失败 false 
             */
            function curlGetHtml($url,$timeout = 25) {

                $ch = curl_init();
                //配置代理
                curl_setopt($ch, CURLOPT_PROXY, "http://dev-proxy.oa.com:8080");
                //跳过ssl检查项。
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                curl_setopt($ch, CURLOPT_URL, $url);
                // TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                // 无需http header信息
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.130 Safari/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

                $output = curl_exec($ch);
                if ($output == FALSE) {
                    print_r("CURL Error:" . curl_error($ch));
                }
                curl_close($ch);
                return $output;
            }

            /**
             * PickupFromGoogle() 
             * 从Google搜索结果文件中提取数据
             * @param string $filePath 
             * @return Array 超链接 分词结果
             * */
            function PickupFromGoogle($filePath) {
                $data = array(
                    'keyword' => array(),
                    'href' => array(),
                    'text' => array(),
                );
                phpQuery::newDocumentFile($filePath);
                //正则表达式，为提取链接中的q参数
                $paHref = '%q=(.*?)&%si';
                //正则表达式，为提取<b>元素
                $paB = '%<b>(.*?)</b>%si';
                $objLinks = pq('body>p>a');
                foreach ($objLinks as $a) {
                    //真正的搜索结果链接中q参数值
                    preg_match($paHref, $a->getAttribute('href'), $match);
                    if (count($match)) {
                        $data['href'][] = $match[1];
                    }
                    $data['text'][] = $a->nodeValue;
                    //提取链接文本中<b>元素内容是标红内容，是分词关键词
                    preg_match($paB, pq($a)->html(), $match);
                    if (count($match) > 0) {
                        $data['keyword'][] = $match[1];
                    }
                }
                $data['keyword'] = array_unique($data['keyword']);
                return $data;
            }

            /**
             * PickupFromBaidu() 
             * 从Baidu搜索结果文件中提取数据
             * @param string $keyWord 用户输入关键词 
             * @return Array 超链接 分词结果
             * */
            function PickupFromBaidu($keyWord) {
                $data = array(
                    'keyword' => array(),
                    'href' => array(),
                    'text' => array(),
                );
                $htmlBaidu = curlGetHtml("https://www.baidu.com/s?wd=" . $keyWord);
                if ($htmlBaidu == FALSE) {
                    echo "百度搜索失败，无法比较";
                    exit();
                }
                phpQuery::newDocument($htmlBaidu);
                //正则表达式，为提取<em>元素
                $paB = '%<em>(.*?)</em>%si';
                $objLinks = pq('div .result h3 a');
                foreach ($objLinks as $a) {
                    //真正的搜索结果链接需是百度的重定向结果
                    $relLink = GetRedirectUrl($a->getAttribute('href'));
                    if ($relLink == FALSE) {
                        $relLink = $a->getAttribute('href');
                    }
                    $data['href'][] = $relLink;
                    $data['text'][] = $a->nodeValue;
                    //提取链接文本中<em>元素内容是标红内容，是分词关键词
                    preg_match($paB, pq($a)->html(), $match);
                    if (count($match) > 0) {
                        $data['keyword'][] = $match[1];
                    }
                }
                $data['keyword'] = array_unique($data['keyword']);
                return $data;
            }

            set_time_limit(120);
            //引入phpQuery.php
            require 'phpQuery\phpQuery.php';

            $fileGoogle = 'files\google.htm';
            //$fileBaidu = 'files\baidu.htm';      
            $dataGoogle = PickupFromGoogle($fileGoogle);
            $dataBaidu = PickupFromBaidu("吃鸡荒野求生");
            ?>
            <div class="row" style="margin-top: 20px"> 
                <div class="col-md-6 col-md-offset-2">
                    <form>
                        <div class="form-group">
                            <input type="text" class="form-control" id="inputText" placeholder="请输入搜索关键字" value="吃鸡荒野求生" readonly="true">
                        </div>
                    </form>
                </div>
                <div class="col-md-2">
                    <button type="button"  class="btn btn-primary">开始搜索</button>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading"> 
                    <h3 class="panel-title">分词结果：</h3> 
                </div> 
                <div class="panel-body"> 
                    <div class="row"> 
                        <div class="col-md-6">
                            <?php
                            foreach ($dataBaidu["keyword"] as $key) {
                                echo $key . "</br>";
                            }
                            ?>
                        </div> 
                        <div class="col-md-6">
                            <?php
                            foreach ($dataGoogle["keyword"] as $key) {
                                echo $key . "</br>";
                            }
                            ?>
                        </div> 
                    </div>
                </div> 
            </div>
            <div class="panel panel-default">
                <div class="panel-heading"> 
                    <h3 class="panel-title">搜索结果：</h3> 
                </div> 
                <div class="panel-body"> 
                    <div class="row"> 
                        <div class="col-md-6">
                            <?php
                            $linkCount = count($dataBaidu["href"]);
                            for ($i = 0; $i < $linkCount; $i++) {
                                echo "<a href=\"javascript:void(0)\" onclick=\"CheckLink('" . $dataBaidu["href"][$i] . "','baidu'," . $i . ")\">" . $dataBaidu["text"][$i] . "</a><br></br>";
                            }
                            ?>
                        </div> 
                        <div class="col-md-6">
                            <?php
                            $linkCount = count($dataGoogle["href"]);
                            for ($i = 0; $i < $linkCount; $i++) {
                                echo "<a href=\"javascript:void(0)\" onclick=\"CheckLink('" . $dataGoogle["href"][$i] . "','google'," . $i . ")\">" . $dataGoogle["text"][$i] . "</a><br></br>";
                            }
                            ?>
                        </div> 
                    </div>
                </div> 
            </div>
        </div> 
        <script>
            var dataGoogle =<?= json_encode($dataGoogle, JSON_UNESCAPED_UNICODE) ?>;
            var dataBaidu =<?= json_encode($dataBaidu, JSON_UNESCAPED_UNICODE) ?>;
            /**
             * 根据用户反馈比对搜索结果的准确性
             * @param {type} link 链接
             * @param {type} source 当前链接来源
             * @param {type} index 当前链接在当前来源中的排序
             * @returns {undefined}
             */
            function CheckLink(link, source, index)
            {
                var indexBaidu;
                var indexGoogle;
                //确定用户点击链接分别在baidu、google的位置
                if (source == "baidu")
                {
                    indexBaidu = index;
                    //当前用户选择的是baidu结果，查看当前链接是否在google结果中
                    indexGoogle = dataGoogle.href.indexOf(link);
                    if (indexGoogle == -1)
                    {
                        indexGoogle = 999;
                    }
                } else
                {
                    indexGoogle = index;
                    //当前用户选择的是google结果，查看当前链接是否在百度结果中
                    indexBaidu = dataBaidu.href.indexOf(link);
                    if (indexBaidu == -1)
                    {
                        indexBaidu = 999;
                    }
                }
                //如果用户选择的连接靠前，更准确即位置越小越准确
                if (indexBaidu < indexGoogle)
                {
                    alert("百度的搜索结果更接近用户的需求");
                } else if (indexBaidu == indexGoogle)
                {
                    alert("百度、Google的搜索结果都接近用户的需求");
                } else
                {
                    alert("Google的搜索结果更接近用户的需求");
                }
                //在新窗口打开链接
                window.open(link);
            }
        </script>
    </body>
</html>



