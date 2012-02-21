<?php
$path = $_SERVER['PATH_INFO'];
$reflection_strength = $_GET['t'];
$reflection_height = $_GET['h'];
$gap = $_GET['g'];

$in = imagecreatefrompng(dirname(__FILE__).'/'.$path);
$h = imagesy($in);                                //    store height of original image
$w = imagesx($in);                                    //    store height of original image
$reflection_height = $h * $reflection_height / 100;
$output_height = $h + $reflection_height + $gap;    //    calculate height of output image

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

// 1. copy each line individually, starting at the 'bottom' of the image, working upwards. 
// 2. set transparency to vary between reflection_strength and 127
// 3. copy line back to mirrored position in original
for($y = 0; $y < $reflection_height; $y++)
{
    $t = ((127-$reflection_strength) + ($reflection_strength * ($y/$reflection_height)));
    imagecopy($reflection_section, $out, 0, 0, 0, $h  - $y, $w, 1);
    imagefilter($reflection_section, IMG_FILTER_COLORIZE, 0, 0, 0, $t);
    imagecopyresized($out, $reflection_section, $a, $h + $y + $gap, 0, 0, $w - (2*$a), 1, $w, 1);
}

// output image to view
header('Content-type: image/png');
imagesavealpha($out,true);
imagepng($out);
?>
