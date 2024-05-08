<?php
session_start();

require 'vendor/autoload.php'; // autoload classes

/*
 * Load the required commonmark classes
 */ 
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

/*
 * Load own website class
 */ 
require_once 'clsWebsite.php';

/*
 * Create an instance of our class
 */ 
$web = new Website;

/*
 * 	URL to your website
 *	public $baseDir = 'https://10.0.2.10/index.php?p=0';
 * 
 *      This will call this index php living in the ROOT
 */
 
 
	$web->baseDir = 'https://kisscms.roelfrenkema.com/index.php?p=0';


/*
 * We can now set commonmark environment wit the config set by our class
 */ 
$environment = new Environment($web->mdConfig);

/*
 * Load commonmark environment
 */
$environment->addExtension(new AttributesExtension());
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new ExternalLinkExtension());
$environment->addExtension(new FootnoteExtension());
$environment->addExtension(new HeadingPermalinkExtension());
$environment->addExtension(new TableExtension());
$environment->addExtension(new TableOfContentsExtension());

/*
 * Here we start the true navigation. at a later time we might use a
 * session setting instead of the query
 */  

$web->parseQuery();

/*
 * Get a standard banner from the current directory.
 * 
 * 3072x1024 
 */ 
    $bannerImage = 'https://kisscms.roelfrenkema.com/'.$web->dirPosition.'/banner.png';

/*
 * Main page
 */ 
if (is_file($web->navDir.'/index.md')) $myContent = $web->navDir.'/index.md';

/*
 * Start of a Blog directory
 */ 
if (is_file($web->navDir.'/blog.md')) {
    $myContent = $web->navDir.'/blog.md';
    $web->blogPointer = true;
}

/*
 * Normal Page
 */ 
if (is_file($web->navDir.'/pagina.md')) $myContent = $web->navDir.'/pagina.md';

/*
 * Current Blog Page
 */ 
if (is_file($web->navDir.'/'.$web->blogNaam)) {
    $myContent = $web->navDir.'/'.$web->blogNaam;
    $blogPointer = true;
}

$r = $web->getInfo($myContent);

if (! array_key_exists('image', $r)) {
    $r['image'] = $bannerImage;
}


?>
<!doctype html>
<html lang="nl">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-brown.css">
    <link rel="stylesheet" href="/gallery.css"> 
    <link rel="stylesheet" href="/site.css"> 

	<?php
    //Vul metatags in
        $web->generate_meta_tags();
?>

	<title><?php echo $GLOBALS['r']['title']; ?></title>

  </head>

  <body class="w3-theme">
	  
<div class="w3-display-container w3-animate-opacity">
  <img src="<?php echo $bannerImage?>" alt="Banner" style="width:100%;min-height:200px;max-height:500px;">
  <div class="w3-display-middle w3-text-black w3-xxlarge" style="text-shadow:2px 2px 0 #fff"><?php echo $r['title'] ?></div>
</div>

  <div id='subhero' class="w3-container w3-center w3-black w3-text-white">
    <p>The easiest CMS in the world.</p>
  </div>	  

<div id='container' class='w3-cell-row'>
 
	<div id='leftmenu' class='w3-container w3-cell w3-col m2 l2'>
		<h3>Menu</h3>
      <?php
    echo $web->generateNavMenu($web->navDir, $web->baseDir);
?>
	</div>
  
	<div id='content' class='w3-container w3-cell w3-white w3-col m8 l8'>
  <br><br>


		<?php
      $converter = new MarkdownConverter($environment);
echo $converter->convert(implode('', $r['lines']));

if (is_file($web->fileDir.$web->dirPosition.'/pagina.md')) {
    $web->disqus();
}
if (is_file($web->fileDir.$web->dirPosition.'/blog.md')) {
    if ($web->blogNaam) {
        $web->disqus();
    } else {
        $web->cardMaker($web->fileDir.$web->dirPosition, $web->numJaar, $web->numMaand);
    }
}


?>
  <br><br>
	</div>
	
    <div id='rightmenu' class='w3-container w3-cell w3-col m2 l2'>
		<h3>We endorse</h3>
		
		<?php
    $tekst = file_get_contents($web->fileDir.'/info.md');

echo $converter->convert($tekst);

if ($web->blogPointer) $web->archief($web->fileDir.$web->dirPosition);

$adds = '';

if (array_key_exists('ad', $r)) {
    foreach ($r['ad'] as $line) {
        $adds .= $line;
    }

    echo '<h3>Adds</h3>';
    echo $converter->convert($adds);
}
?>
       <h3>Support me with your stars on GitHub.</h3>
      <a href="https://github.com/roelfrenkema/KISS_CMS" alt=githublink>Repository</a>

</div>

</div>   
  <div id='footer' class="w3-container w3-black w3-text-white">
    <p class='w3-center'>
	  Email: 
     <script>var a = new Array('.c','ke','en','ro','-b','xs','of','h@','el','fr','ma','om');document.write("<a href='mailto:"+a[5]+a[4]+a[6]+a[7]+a[3]+a[8]+a[9]+a[2]+a[1]+a[10]+a[0]+a[11]+"'>"+a[5]+a[4]+a[6]+a[7]+a[3]+a[8]+a[9]+a[2]+a[1]+a[10]+a[0]+a[11]+"</a>");</script>
     - &copy; <a property="dct:title" rel="cc:attributionURL" href="http://blog.roelfrenkema.com">blog.roelfrenkema.com</a> by <a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://blog.roelfrenkema.com/index.php?dir=04.Artikelen&jaar=2023&maand=05&blog=202305252118-Wie_ben_ik.md">Roelf Renkema</a> is licensed under <a href="http://creativecommons.org/licenses/by-nc-nd/4.0/?ref=chooser-v1" target="_blank" rel="license noopener noreferrer" style="display:inline-block;">CC BY-NC-ND 4.0
     <img alt="creativecommons" style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg?ref=chooser-v1">
     <img alt="creativecommons" style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg?ref=chooser-v1">
     <img alt="creativecommons" style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nc.svg?ref=chooser-v1">
     <img alt="creativecommons" style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nd.svg?ref=chooser-v1"></a>
   </p>
  </div>

</body>
</html>
