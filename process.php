<?php

function image_fix_orientation(&$image, $filename) {
    $exif = exif_read_data($filename);
    $change = 0;

    if (!empty($exif['Orientation'])) {
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                $change = 1;
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                $change = 1;
                break;
        }
    }
    return $change;
}

function watermark_image($path, $image, $watermark, $highres = false, $font_path = '/usr/share/fonts/truetype/Helvetica/HelveticaNeue-Light.ttf') {
	$accepted_files = array('jpg', 'jpeg', 'png');
	$img_src = $path . $image;

	$file_info = pathinfo($path.$image);

	switch ($file_info['extension']) {
		case 'jpeg':
			$img = imagecreatefromjpeg($img_src);
			$change = image_fix_orientation($img, $img_src);
			break;
		case 'jpg':
			$img = imagecreatefromjpeg($img_src);
			$change = image_fix_orientation($img, $img_src);
			break;
		case 'png':
			$img = imagecreatefrompng($img_src);
			break;
	}

	if(in_array($file_info['extension'], $accepted_files)) {

		$text_colour = imagecolorallocate($img, 160, 160, 160);


		if($change == 0) {
			list($img_width, $img_height,,) = getimagesize($img_src);
		} else {
			list($img_height, $img_width,,) = getimagesize($img_src);
		}

		// find font-size for $txt_width = 80% of $img_width...
		$font_size = 1; 
		$txt_max_width = intval(0.7 * $img_width);    

		do {

		    $font_size++;
		    $p = imagettfbbox($font_size,0,$font_path, $watermark);
		    $txt_width=$p[2]-$p[0];
		    // $txt_height=$p[1]-$p[7]; // just in case you need it

		} while ($txt_width <= $txt_max_width);

		// now center text...
		$y = $img_height * 0.5; // baseline of text at 90% of $img_height
		$x = ($img_width - $txt_width) / 2;

		imagettftext($img, $font_size, 0, $x, $y, $text_colour, $font_path, $watermark);
		rename($path.$image, $path.'orig/'.$file_info['filename'] . '.' . $file_info['extension']);

		switch ($file_info['extension']) {
			case 'jpeg':
				imagejpeg($img, $path.$image);
				break;
			case 'jpg':
				imagejpeg($img, $path.$image);
				break;
			case 'png':
				imagepng($img, $path.$image);
				break;
		}
	imagedestroy($img);
	}

	// Now process in the case that it's a movie file

	if(in_array($file_info['extension'], array('mp4','mov'))) {
		// Move original file to a safe place
		rename($path.$image, $path.'orig/'.$file_info['filename'] . '.' . $file_info['extension']);

		// Get media info
		$mediainfo = shell_exec('mediainfo --output=XML ' . $path .'orig/' . $image);
		$xml = simplexml_load_string($mediainfo);
		$out = print_r($xml, true);

		if($xml->File->track[1]->Rotation == '180?') {
			$rotation = 'transpose=1,transpose=1,';
		} elseif($xml->File->track[1]->Rotation == '90?') {
			$rotation = 'transpose=1,';
		} else {
			$rotation = '';
		}

		// Watermark file
		$shellcmd = 'ffmpeg -i ' . $path . 'orig/' . $image . ' -strict -2 -metadata:s:v:0 rotate=0 -vf ' . $rotation . 'format=yuv420p,drawtext="fontfile=' . $font_path . ':text=' . $watermark . ':fontcolor=gray@1.0:fontsize=' . ($xml->File->track[1]->Width)/15 . ':x=(w-text_w)/2: y=(h-text_h-line_h)/2" -y ' . $path . $image;
		shell_exec($shellcmd);

		//Generate GIF
		$shellcmd = 'ffmpeg  -i ' . $path . 'orig/'  . $image . ' -strict -2 -metadata:s:v:0 rotate=0 -vf ' . $rotation . 'scale=320:-1,drawtext="fontfile=' . $font_path . ':text=' . $watermark . ':fontcolor=gray@1.0:fontsize=30:x=(w-text_w)/2: y=(h-text_h-line_h)/2" -r ' . ($highres == true ? 25 : 10) . ' -f image2pipe -vcodec ppm - | convert -delay ' . ($highres == true ? 4 : 10) .' -loop 0 - gif:- | convert -layers Optimize - ' . $path . 'gif/' . $image . '.gif';
		//file_put_contents('log.txt', $shellcmd);
		shell_exec($shellcmd);
	}
}


?>
