<?php
namespace booosta\imagefile;

use \booosta\Framework as b;
b::init_module('imagefile');

class Imagefile extends \booosta\uploadfile\Uploadfile
{
  use moduletrait_imagefile;

  protected $glib = 'gd';    // gd, imagick or gmagick

  public function set_glib($glib) { $this->glib = $glib; }


  public function get_html($width = null, $height = null, $func = null)
  {
    if($func == null):
      if($width) $widthstr = "width='$width'";
      if($height) $heightstr = "height='$height'";;
    elseif($func == 'max'):
      if($width && !$height) $height = 1000000;
      if(!$width && $height) $width = 1000000;
      $widthstr = "width='" . $this->getNewWidth($this->get_width(), $this->get_height(), $width, $height) . "'";
      $heightstr = "height='" . $this->getNewHeight($this->get_width(), $this->get_height(), $width, $height) . "'";
    endif;

    $tag = "<img src='" . $this->get_url() . "' border='0' $widthstr $heightstr>";
    return $tag;
  }


  public function resize($maxWidth, $maxHeight) 
  { 
    $func = "resize_$this->glib";
    #\booosta\debug("func: $func");
    return $this->$func($maxWidth, $maxHeight); 
  }


  public function resize_gd($maxWidth, $maxHeight)
  {
    #\booosta\debug('resize_gd');
    $img = $this->get_url();
    $tmp_image = $this->get_newimage($img);
    #\booosta\debug($tmp_image);
    if($tmp_image === false) return false;

    $width = imagesx($tmp_image);
    $height = imagesy($tmp_image);

    //calculate the image ratios
    $imgratio = $width / $height;
    $maxratio = $maxWidth / $maxHeight;

    if($width < $maxWidth && $height < $maxHeight):
      $new_width = $width;
      $new_height = $height;
    elseif($imgratio > $maxratio):
      $new_width = $maxWidth;
      $new_height = $maxWidth / $imgratio;
    else:
      $new_height = $maxHeight;
      $new_width = $maxHeight * $imgratio;
    endif;

    $new_image = imagecreatetruecolor($new_width,$new_height);
    $result = ImageCopyResampled($new_image, $tmp_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    #\booosta\debug("result ImageCopyResampled: $result");

    $newfilename = $this->rawname . '.png';
    $this->set('filename', $newfilename);
    return imagepng($new_image, $this->pathname . '/' . $newfilename); 
  }


  public function resize_imagick($maxWidth, $maxHeight)
  {
    $img = $this->get_url();
    $image = new Imagick($img);

    if(true !== $image->scaleImage($maxWidth, $maxHeight, true)) return false;

    # somtimes scaleImage works incorrect
    $newwidth = $image->getImageWidth();
    $newheight = $image->getImageHeight();

    if($newwidth > $maxWidth || $newheigth > $maxHeight):
      $proportion = $newwidth/$newheight;
      $maxproportion = $maxWidth/$maxHeight;

      if($proportion > $maxproportion):  // image to scale to maxwidth
        $width = $maxWidth;
        $height = $width / $proportion;
      else:   // image to scale to maxheight
        $heigth = $maxHeigth;
        $width = $height * $proportion;
      endif;

      $image->scaleImage($width, $height, false);
    endif;

    return $image->writeImage($this->get_url());
  }


  public function resize_gmagick($maxWidth, $maxHeight, $quality = false)
  {
    $img = $this->get_url();
    $image = new Gmagick($img);
    
    if($image->getimagewidth() > $maxWidth || $image->getimageheight() > $maxHeight)
      $image->thumbnailImage($maxWidth, $maxHeight, true);

    if($quality !== false) $image->setCompressionQuality($quality);
    
    $image->write($this->get_url());
  }
  

  public function getNewHeight($isWidth, $isHeight, $maxWidth, $maxHeight)
  {
    if($isWidth == 0) $isWidth = $this->get_width();
    if($isHeight == 0) $isHeight = $this->get_height();

    $heightFactor = min(1, $maxHeight/$isHeight);
    $widthFactor = min(1, $maxWidth/$isWidth);
    $factor = min($heightFactor, $widthFactor);
  
    return round($isHeight * $factor);
  }
  
  
  public function getNewWidth($isWidth, $isHeight, $maxWidth, $maxHeight)
  {
    if($isWidth == 0) $isWidth = $this->get_width();
    if($isHeight == 0) $isHeight = $this->get_height();

    $heightFactor = min(1, $maxHeight/$isHeight);
    $widthFactor = min(1, $maxWidth/$isWidth);
    $factor = min($heightFactor, $widthFactor);
  
    return round($isWidth * $factor);
  }


  public function get_width()
  {
    $func = "get_width_$this->glib";
    return $this->$func();
  }


  protected function get_width_gd()
  {
    #\booosta\debugx('url inside: ' . $this->get_url());
    $picsize = GetImageSize($this->get_url());
    return $picsize[0];
  }


  protected function get_width_gmagick()
  {
    $img = $this->get_url();
    $image = new Gmagick($img);
    return $image->getImageWidth();
  }


  protected function get_width_imagick()
  {
    $img = $this->get_url();
    $image = new Imagick($img);
    return $image->getImageWidth();
  }


  public function get_height()
  {
    $func = "get_height_$this->glib";
    return $this->$func();
  }


  protected function get_height_gd()
  {
    $picsize = GetImageSize($this->pathfilename);
    return $picsize[1];
  }    


  protected function get_height_imagick()
  {
    $img = $this->get_url();
    $image = new Imagick($img);
    return $image->getImageHeight();
  }

  protected function get_height_gmagick()
  {
    $img = $this->get_url();
    $image = new Gmagick($img);
    return $image->getImageHeight();
  }
}
