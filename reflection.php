<?php
$string = file_get_contents(dirname(__FILE__).$_SERVER['PATH_INFO']);
$in = imagecreatefromstring($string);

$transparency   = isset($_GET['t'])? $_GET['t'] : 50;
$size           = isset($_GET['h'])? $_GET['h'] : 50;
$gap            = isset($_GET['g'])? $_GET['g'] : 20;

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

// output image to view
header('Content-type: image/png');
imagesavealpha($out,true);
imagepng($out);
?>