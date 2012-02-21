<?php
######## SETTINGS ##############################################################

define('REFLECTION_DIR', dirname(__FILE__).'/reflection/');

################################################################################

$dir = substr(REFLECTIONDIR, 0, strlen(REFLECTIONDIR) - 1);

if (!file_exists($dir)) {
    @mkdir($dir)
    or die("The directory '$dir' could not be created.");
}

if (!is_writable($dir)) {
    die("The directory '$dir' isn't writable.");
}

$original = dirname(__FILE__).$_SERVER['PATH_INFO'];

$transparency   = isset($_GET['t'])? $_GET['t'] : 50;
$size           = isset($_GET['h'])? $_GET['h'] : 50;
$gap            = isset($_GET['g'])? $_GET['g'] : 20;

$filename = pathinfo($original, PATHINFO_FILENAME);
$filepath = REFLECTION_DIR.$filename."_t{$transparency}_h{$size}_g{$gap}.png";

// Send headers
header('Content-type: image/png');
header('Expires: 01 Jan '.(date('Y') + 10).' 00:00:00 GMT');
header('Cache-control: max-age=2903040000');

if(file_exists($filepath)) {

	// The file exists, is it cached by the browser?
	if (function_exists('apache_request_headers')) {
		$headers = apache_request_headers();

		// We don't need to check if it was actually modified since then as it never changes.
		$responsecode = isset($headers['If-Modified-Since'])? 304 : 200;
	}
	else {
		$responsecode = 200;
	}

	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filepath)) . ' GMT', true, $responsecode);

	if ($responsecode == 200) {
		header('Content-Length: '.filesize($filepath));
		die(file_get_contents($filepath));
	}
}
else {
    $string = file_get_contents($original);
    $in = imagecreatefromstring($string);

    $h = imagesy($in); //height
    $w = imagesx($in); //width

    $size           = $h * $size / 100;
    $output_height  = $h + $size + $gap;

    // create new image to use for output. fill with transparency. ALPHA BLENDING MUST BE FALSE
    $out = imagecreatetruecolor($w, $output_height);
    imagealphablending($out, false);
    $bg = imagecolortransparent($out, imagecolorallocatealpha($out, 255, 255, 255, 127));
    imagefill($out, 0, 0, $bg);
    
    // copy original image onto new one, leaving space underneath for reflection and 'gap'
    imagecopyresampled($out, $in , 0, 0, 0, 0, $w, $h, $w, $h);
    
    // create new single-line image to act as buffer while applying transparency
    $reflection_section = imagecreatetruecolor($w, 1);
    imagealphablending($reflection_section, false);
    $bg1 = imagecolortransparent($reflection_section, imagecolorallocatealpha($reflection_section, 255, 255, 255, 127));
    imagefill($reflection_section, 0, 0, $bg1);
    
    for($y = 0; $y < $size; $y++){
        $t = ((127 - $transparency) + ($transparency * ($y / $size)));
        imagecopy($reflection_section, $out, 0, 0, 0, $h - $y, $w, 1);
        imagefilter($reflection_section, IMG_FILTER_COLORIZE, 0, 0, 0, $t);
        imagecopyresized($out, $reflection_section, 0, $h + $y + $gap, 0, 0, $w, 1, $w, 1);
    }
    
    imagesavealpha($out,true);
    
    // Check PHP version to solve a bug that caused the script to fail on PHP versions < 5.1.7
    if (strnatcmp(phpversion(), '5.1.7') >= 0) {
        imagepng($out, $filepath, 0, NULL);
    }
    else {
        imagepng($out, $filepath);
    }
    
    imagepng($out);
    
    // Free up memory
    imagedestroy($out);
}