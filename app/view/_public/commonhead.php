<!DOCTYPE html>
<html lang="zh-cn">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="description" content="<?php if(!empty($this->_seo['description'])){echo $this->_seo['description'];}else{echo '';}?>">
	<meta name="keywords" content="<?php if(!empty($this->_seo['keywords'])){echo $this->_seo['keywords'];}else{echo '';}?>">
	<meta name="author" content="">
	<title><?php if(!empty($this->_seo['title'])){echo $this->_seo['title'];}else{echo '';}?></title>
	<?php echo paramCSS('res/lib3rd/bootstrap-3.3.2-dist/css/bootstrap.min.css', 'res/css/admin/dashboard.css');?>
	<?php echo paramJS('res/js/jquery/jquery-1.10.2.min.js', 'res/lib3rd/bootstrap-3.3.2-dist/js/bootstrap.min.js');?>
	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
</head>

<script type="text/javascript">
	var RES_DOMAIN = '<?php echo RES_DOMAIN;?>';
</script>

<body>