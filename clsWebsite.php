<?php

class Website
{
    
    const staticMeta = '  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
  <link rel="manifest" href="/site.webmanifest">
';
    const archiveHead = '
<h3>Archief</h3>
<div class="w3-bar-block" style"width="80%">
';  
    public $mdConfig = [
    'html_input' => 'allow',
    'external_link' => [
        'internal_hosts' => 'blog.roelfrenkema.com', // TODO: Don't forget to set this!
        'open_in_new_window' => true,
        'html_class' => 'external-link',
        'nofollow' => '',
        'noopener' => 'external',
        'noreferrer' => 'external',
    ],
    'footnote' => [
        'bacuse League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
kref_class' => 'footnote-backref',
        'backref_symbol' => '↩',
        'container_add_hr' => true,
        'container_class' => 'footnotes',
        'ref_class' => 'footnote-ref',
        'ref_id_prefix' => 'fnref:',
        'footnote_class' => 'footnote',
        'footnote_id_prefix' => 'fn:',
    ],
    'table_of_contents' => [
        'html_class' => 'table-of-contents',
        'position' => 'placeholder',
        'style' => 'bullet',
        'min_heading_level' => 1,
        'max_heading_level' => 6,
        'normalize' => 'relative',
        'placeholder' => '[TOC]',
    ],
    'heading_permalink' => [
        'insert' => 'after',
    ],
    'table' => [
        'wrap' => [
            'enabled' => false,
            'tag' => 'div',
            'attributes' => ['class' => 'blogtable'],
        ],
        'alignment_attributes' => [
            'left' => ['align' => 'left'],
            'center' => ['align' => 'center'],
            'right' => ['align' => 'right'],
        ],
    ],

];

    public $pageInfo = [];
    public $fileAuthor = 'Roelf Renkema';
    public $galleryImg = '';
    public $statDir = '';
    public $dirPosition ='sample/'; //static rootdirectory
    public $fileDir = ''; //static filedir set in construct
    public $baseDir = '';

    public $navDir = ''; //The dynamic navigation directory
    public $linkUri = ''; //page link that populate query 
    public $blogPointer = false;
    public $archiefPointer = false;
    public $blogNaam = '';
    public $numJaar = 2000;
    public $numMaand = 1;
    public $disqus = "";
/*
 * Your domain. We will asume you set up a secure website and use
 * https:// to access it.
 */ 
    public $myDomain = ""; 


    public function __construct($myDomain){

	$this->numJaar = date('Y');
	$this->numMaand = date('m');
 	$this->fileDir = $_SERVER['DOCUMENT_ROOT']."/"; 
	$this->baseDir = 'https://'.$myDomain.'/index.php?p=0';
	$this->myDomain = $myDomain;
    }

    public function parseQuery(){

	parse_str($_SERVER['QUERY_STRING'], $queryArray);

	if (isset($queryArray['dir'])){ 
	    $this->dirPosition .= $queryArray['dir'];
	}else{
	    $queryArray['dir'] = '';
	}
	$this->navDir = $this->fileDir.$this->dirPosition;

//var_dump($this->navDir);

	if (isset($queryArray['blog'])) $this->blogNaam = $queryArray['blog'];

	if (isset($queryArray['jaar'])) $this->numJaar = $queryArray['jaar'];

	if (isset($queryArray['maand'])) $this->numMaand = $queryArray['maand'];

	if (isset($queryArray['a'])) $this->archiefPointer = $queryArray['a'];

	$this->linkUri = $this->baseDir.'&dir='.$queryArray['dir'].'&jaar='.$this->numJaar.'&maand='.$this->numMaand.'&blog=';

//var_dump($this->linkUri);

    }
    
    public function createMarkdownImageGallery($line)
    {
	$granate = explode(" ",$line);
	$directory = trim($granate[1]);
        $images = glob($directory.'/*.png');
        $markdown = '';

        foreach ($images as $image) {
            $imageName = basename($image);
            $markdown .= "[![$imageName]($image){: height=\"200px\" width=\"auto\"}]($image){:target=\"_blank\"}";
	    $this->galleryImg = $image;
        }
//	$this->galleryImg = $image;
	return $markdown."\n";
    }
    
    function getInfo($fileNaam)
    {
	// uses a local r
	// prepareer de arrays
	$r['ad'] = [];
	$r = ['lines' => []];

	$r['modtime'] = filemtime($fileNaam);

	$r['lines'] = file($fileNaam);

	$myBase = basename($fileNaam, '.md');
	$baseExplode = explode('-', $myBase);
	$r['datum'] = date('Y-m-d - H:i', strtotime($baseExplode[0]));

	// title, intro, image, ad, gallery
	foreach ($r['lines'] as $key => $line) {

	    //Get our title
	    if (preg_match('/^#[^#]/', $line)){
		 $r['title'] = trim(substr($line, 1));

	    // Get our introline
	    } elseif (preg_match('/^[a-zA-Z0-9]/', $line) && ! array_key_exists('intro', $r)) {
		$r['intro'] = trim($line);

	    // Find the first image
	    } elseif (((strpos($line, '![') === 0) || (strpos($line, '[![') === 0)) && ! array_key_exists('image', $r)) {
		$start = strpos($line, '(') + 1;
		$length = strpos($line, ')') - $start;
		$r['image'] = trim(substr($line, $start, $length));

	    // Hashtags to metaline
	    } elseif (preg_match('/^Hashtags/', $line)) {
		$hashtags = substr($line, 8);
		$hashtags = str_replace('#', ',', $hashtags);
		$hashtags = str_replace(' ', '', $hashtags);
		$r['keywords'] = substr($hashtags, 2);

	    // Gallery creator
	    } elseif (preg_match('/^Gallery/', $line)) {
		$replacement = $this->createMarkdownImageGallery($line);
		$r['lines'][$key] = $replacement;

	    // Amazon adds system
	    } elseif (strpos($line, 'amzn') !== false) {
		$r['ad'][] = trim($line);
		unset($r['lines'][$key]);         
	    }
	}
	
	// TODO loose the R at least keep it local.
	$this->pageInfo = $r;

	if(! array_key_exists('image', $this->pageInfo)) $this->pageInfo['image'] = $this->galleryImg;
	if(! array_key_exists('title', $this->pageInfo)) $this->pageInfo['title'] = "no title";
	if($this->pageInfo['image'] === "") $this->pageInfo['image'] = "images/assets/imageNotFound.png";
	if(! array_key_exists('keywords', $this->pageInfo)) $this->pageInfo['keywords'] = 'page';

    
	return $r;
    }
// Genereert de metatags
    function generate_meta_tags()
    {
	//we need to kill the globals here.
	//Favicons
	echo Website::staticMeta;
    
	// Normal SEO meta tags
	echo '<meta name="author" content="'.$this->fileAuthor.'">'."\r\n";
	echo '<meta name="description" content="'.$this->pageInfo['intro'].'">'."\r\n";
	echo '<meta name="keywords" content="'.trim($this->pageInfo['keywords']).'">'."\r\n";
	echo '<meta name="robots" content="index, follow">'."\r\n";

	// Facebook meta tags
	echo '<meta property="og:title" content="'.$this->pageInfo['title'].'">'."\r\n";
	echo '<meta property="og:description" content="'.$this->pageInfo['intro'].'">'."\r\n";
	echo '<meta property="og:image" content="https://blog.roelfrenkema.com/'.$this->pageInfo['image'].'">'."\r\n";
	echo '<meta property="og:url" content="'.$this->linkUri.$this->blogNaam.'">'."\r\n";

	// Twitter meta tags
	echo '<meta name="twitter:card" content="summary_large_image">'."\r\n";
	echo '<meta name="twitter:title" content="'.$this->pageInfo['title'].'">'."\r\n";
	echo '<meta name="twitter:description" content="'.$this->pageInfo['intro'].'">'."\r\n";
	echo '<meta name="twitter:image" content="https://blog.roelfrenkema.com/'.$this->pageInfo['image'].'">'."\r\n";

    }

    function cardMaker()
    {
    /*
     * Here we create the cards for the blog overview
     * atm this function is called from the index php
     */ 

	/*
	 * We need to create a search needle
	 */ 
	$myNeedle = $this->fileDir.$this->dirPosition.'/'.$this->numJaar.$this->numMaand.'*.md';

	/*
	 *  We retrieve our current uri
	 */ 
	$locUri = $this->linkUri;

	/*
	 *  Collect the filenames we need for our cards.
	 */ 
	$cardFiles = glob($myNeedle);

	/*
	 * If we cant find any files we still present a placeholder.
	 */ 
	if (! $cardFiles) {
	    $cardFiles[] = $_SERVER['DOCUMENT_ROOT'].'/'.$this->statDir.'195912110200-Placeholder.md';
	    $locUri = 'https://'.$this->myDomain.'/index.php?p=0&dir=&jaar=1959&maand=12&blog=';
	}

	/* 
	 * Sort our cards, newest first for pocessing
	 */
	rsort($cardFiles);

	/*
	 *  Start The presentation by using w3c w3-row class
	 */ 
	echo '<div class="w3-row">';
	
	

	/*
	 * And start itterating trough the files
	 */ 
	foreach ($cardFiles as $fileNaam) {

	    /*
	     *  Get info on the file from the getInfo routine
	     */ 
	    $locInfo = $this->getInfo($fileNaam);
	    
	    
	    // TODO ???????? Moet die niet hoger?
	    echo '<div class="w3-half w3-container w3-white">';
	    echo '<div class="w3-card-4">';

	    echo '<header class="w3-container w3-theme"><h4>'.$this->pageInfo['title'].'</h4></header>';

	    echo '<div class="w3-container w3-theme-l5">
				';

	    if (isset($this->pageInfo['image'])) {
                $baseImage = $this->cardImagev2($this->pageInfo['image']);
                echo '<img class="w3-image" style="width:100%;" src="'.$baseImage.'" alt="Card Image">';
	    }
	    
	    //pretify text we need to display
	    $first = wordwrap($this->pageInfo['intro'],160,"\n" );
	    $second = explode("\n", $first);
	    echo '<p>'.trim($second[0]).'</p></div>';
	    
	    parse_str($_SERVER['QUERY_STRING'], $queryArray);

	    echo '<footer class="w3-container w3-theme-d5"><br><span><a href="'.$locUri.basename($fileNaam).'">Continue!</a></span><span style="float:right">'.$this->pageInfo['datum'].'</span><br><br></footer>';

	    echo '</div><br></div>';
	}
	echo '</div>';
    }

    function generateNavMenu($dir, $baseDir)
    {
	// Open the directory
	$handle = opendir($dir);

	// Start with an empty list of directories and files
	$dirs = [];
	$files = [];

	// Loop through the directory entries
	while (false !== ($entry = readdir($handle))) {
	    // Ignore . and ..
	    if ($entry == '.' || $entry == '..') continue;

	    // Add directories to the list of directories
	    if (is_dir($dir.'/'.$entry)) $dirs[] = $entry;

	    // Add files to the list of files
	    if (is_file($dir.'/'.$entry)) $files[] = $entry;
	}

	// Close the directory
	closedir($handle);

	// Sort the directories and files alphabetically
	sort($dirs);
	sort($files);

	// Start the navigation menu
	$navMenu = '<div class="w3-bar-block" style="width: 80%">';

	// start unorderd list
	$navMenu .= '
	<a class="w3-bar-item w3-button w3-round-large w3-theme-l1 " style="width:80%" href="'.$baseDir.'">Main menu</a>
	';
	parse_str($_SERVER['QUERY_STRING'], $queryArray);

	if (array_key_exists('blog', $queryArray)) {
	    $navMenu .= '<a class="w3-bar-item w3-button w3-round-large w3-theme-l1 " style="width:80%" href="'.$baseDir.'&jaar='.$queryArray['jaar'].'&maand='.$queryArray['maand'].'&dir='.$queryArray['dir'].'">Blogmenu</a>';
	}

	// Add links to the directories
	foreach ($dirs as $d) {
	    if (strpos($d, '.')) {
		$tNaam = explode('.', $d);
		$dirNaam = $tNaam[1];
	    } else {
		$dirNaam = $d;
	    }
	    $navMenu .= '<a class="w3-bar-item w3-button w3-round-large w3-theme-l1 " style="width:80%" href="'.$baseDir.'&dir='.htmlentities($d).'">'.$dirNaam.'</a>';
	}

	// Add links to the files
	foreach ($files as $f) {
	    if (! $f == 'index.md') {
		$navMenu .= '<a class="w3-bar-item w3-button w3-round-large w3-theme-l1 " style="width:80%" href="'.$baseDir.'&page='.htmlentities($f).'">'.htmlentities($f).'</a>';
	    }
	}

	// End the navigation menu
	$navMenu .= '</div><br><br>';

	// Return the navigation menu
	return $navMenu;
    }
    function disqus()
    {
	parse_str($_SERVER['QUERY_STRING'], $queryArray);
	$myId = $this->blogNaam;
	$myLink = $this->linkUri.$this->blogNaam;

	if (! $myId) {
	    $myId = $queryArray['dir'];
	    $myLink = $this->baseDir.'&dir='.$queryArray['dir'];
	}

	echo "<br><br><div id='disqus_thread'></div>
<script>
    /**
    *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
    *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables    */
    
    var disqus_config = function () {
    this.page.url = '".$myLink."';  // Replace PAGE_URL with your page's canonical URL variable
    this.page.identifier = '".$myId."'; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
    };
    
    (function() { // DON'T EDIT BELOW THIS LINE
    var d = document, s = d.createElement('script');
    s.src = 'https://".$this->disqus.".disqus.com/embed.js';
    s.setAttribute('data-timestamp', +new Date());
    (d.head || d.body).appendChild(s);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href='https://disqus.com/?ref_noscript'>comments powered by Disqus.</a></noscript>
";

	echo '<br><br>';
    }

    // Genereert Archief links voor rechter menu
    function archief($dir)
    {

	echo Website::archiveHead;

	$currentYear = intval(date('Y'));
	$currentMonth = intval(date('m'));

	$startYear = 2022;
	$startMonth = 10;

	$archiveLinks = [];

	for ($year = $startYear; $year <= $currentYear; $year++) {
	    $endMonth = ($year == $currentYear) ? $currentMonth - 1 : 12;
	    $monthRange = range($startMonth, $endMonth);

	    parse_str($_SERVER['QUERY_STRING'], $queryArray);
	    
	    foreach ($monthRange as $month) {
		$formattedMonth = date('F', mktime(0, 0, 0, $month, 1, 0));
		$archiveMonth = date('m', mktime(0, 0, 0, $month, 1, 0));
		$formattedYear = ($year == $currentYear && $month == $currentMonth) ? '' : $year;
		$qTest = glob($dir.'/'.$year.$archiveMonth.'*');
		if ($qTest) {
		    $archiveLinks[] = '<a class="w3-bar-item w3-button w3-round-large w3-theme-l1" style="width:80%" href="index.php?p=0&dir='.$queryArray['dir'].'&jaar='.$year.'&maand='.$archiveMonth.'">'.$formattedMonth.' '.$formattedYear.'</a>'."\n";
		}
	    }

	    $startMonth = 1; // Reset start month to 1 for subsequent years
	}

	// Reverse the order of archive links
	$archiveLinks = array_reverse($archiveLinks);

	// Output the archive links
	foreach ($archiveLinks as $link) {
	    echo $link;
	}
	echo '</div>';
    }

 
    function cardImagev2($image)
    {
    	if (!$image) return;
	$image = trim($image);
	if (substr($image,0,1) === "/") $image = substr($image,1);
	
    	$locImage = new Imagick($image);
    
    	// Crop the image to a 4:3 aspect ratio
    	$locImage->cropThumbnailImage(307, 102);
    	$locImage->setImageFormat("png");
    
    	// Get the image blob as PNG
    	$blob = $locImage->getImageBlob();
    
    	// Destroy the Imagick object to release resources
    	$locImage->destroy();
    
    	// Encode the blob to base64
    	$base64Data = base64_encode($blob);
    
    	// Construct the data URI scheme compatible string with necessary headers
    	$dataUriScheme = "data:image/png;base64," . $base64Data;
    
    	return $dataUriScheme;
    }
    public function getBanner(){
	if(is_file($this->fileDir.'/'.$this->dirPosition.'banner.png')){
	    $bannerImage = '/'.$this->dirPosition.'banner.png';
	}else{
	    $bannerImage = "/images/assets/banner.png";
	}
	return $bannerImage;
    }
}
