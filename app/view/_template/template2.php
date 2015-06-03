<?php
//模板 即控制各个部分的加载顺序的~~ 默认对应的view文件就是中间。。

//页面模板之前增加
$viewfiles = $this->includeviewmod($viewfiles,__DIR__.'/../_public/head1.php');

//页面模板之后增加
$viewfiles = $this->includeviewmod($viewfiles,__DIR__.'/../_public/foot1.php','end');