<?php echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
 <head>
  <title><?php echo $this->doc->title ?></title>
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-15" />
  <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_SSL ?>/custom/styles/style.css" />
  <script src="<?php echo WEBSITE_SSL ?>/javascript/utils.js" type="text/javascript"></script>
  <?php echo $this->doc->header ?>
 </head>
 <body>
 <div id="header">
 <div id="home_header1">
  <img src="/custom/images/pages_header_banner.jpg" alt="" width="1000" height="120" usemap="Map" />
  <map id="Map"><area shape="rect" alt="Bandeau" coords="0,95,900,120" href="<?php echo WEBSITE_SSL ?>"/></map>
 </div>
 </div>
	<?php
	$this->doc->includeErrors();
	echo $this->doc->body;
	if ($layout)
		require_once ($layout);
	?>
</body>
</html>