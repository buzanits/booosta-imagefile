<?php
namespace booosta\imagefile;

\booosta\Framework::add_module_trait('webapp', 'imagefile\webapp');
\booosta\Framework::add_module_trait('base', 'imagefile\base');

trait webapp
{
  protected function uploaded_image($name, $dir = 'upload/', $preservename = false, 
                                    $maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null, $minError = false)
  {
    $upfile = $this->makeInstance('imagefile', $name, $dir, $preservename);
    if(!$upfile->is_valid()) return null;

    if(is_numeric($minWidth) && $upfile->get_width() < $minWidth):
      if($minError) $this->raise_error($this->t('Image must have minimal size') . " $minWidth x $minHeight");
      else return null;
    endif;

    if(is_numeric($minHeight) && $upfile->get_height() < $minHeight):
      if($minError) $this->raise_error($this->t('Image must have minimal size') . " $minWidth x $minHeight");
      else return null;
    endif;

    if($maxWidth || $maxHeight):
      if($maxWidth == null) $maxWidth = 999999;
      if($maxHeight == null) $maxHeight = 999999;
      $result = $upfile->resize($maxWidth, $maxHeight);
    endif;

    return $upfile->get_filename();
  }

  protected function replace_uploaded_image($field, $name, $dir = 'upload/', $preservename = false, $obj = null, 
                                            $maxWidth = null, $maxHeight = null, $minWidth = null, $minHeight = null, 
                                            $minError = false, $maxError = 'resize')
  {
    if(is_object($obj)) $field_content = $obj->get($field);
    else $field_content = $this->get_data($field);

    $upfile = $this->makeInstance('imagefile', $name, $dir, $preservename);
    if($upfile->is_valid()):
      if(is_numeric($minWidth) && $upfile->get_width() < $minWidth):
        if($minError) $this->raise_error($this->t('Image must have minimal size') . " $minWidth x $minHeight");
        else return null;
      endif;

      if(is_numeric($minHeight) && $upfile->get_height() < $minHeight):
        if($minError) $this->raise_error($this->t('Image must have minimal size') . " $minWidth x $minHeight");
        else return null;
      endif;

      if(($maxWidth && $maxWidth != $upfile->get_width()) || ($maxHeight && $maxHeight =! $upfile->get_height())):
        if($maxError == 'resize'):
          if($maxWidth == null) $maxWidth = 999999;
          if($maxHeight == null) $maxHeight = 999999;
          $result = $upfile->resize($maxWidth, $maxHeight);
        elseif($maxError == 'error'):
          $this->raise_error($this->t('Image must have maximal size') . " $maxWidth x $minHeight");
        elseif($maxError == 'ratio'):
          if($upfile->get_width() / $upfile->get_height() != $maxWidth / $maxHeight):
            $this->raise_error($this->t('Image must have maximal size') . " $maxWidth x $minHeight");
          else:
            if($maxWidth == null) $maxWidth = 999999;
            if($maxHeight == null) $maxHeight = 999999;
            $result = $upfile->resize($maxWidth, $maxHeight);
          endif;
        endif;
      endif;

      unlink($dir . $field_content);
      return $upfile->get_filename();
    else:
      return $field_content;
    endif;
  }

  protected function remove_uploaded_image($field, $dir = 'upload/', $obj = null)
  {
    return $this->remove_uploaded_file($field, $dir, $obj);
  }
}


trait Base
{
  protected function get_newimage($filename, $glib = 'gd')
  {
    $path = pathinfo($filename);
    $extension = $path['extension'];

    if($glib == 'gd'):
      if(strtolower($extension) == 'jpg' || strtolower($extension) == 'jpeg') return imagecreatefromjpeg($filename);
      if(strtolower($extension) == 'png') return imagecreatefrompng($filename);
      if(strtolower($extension) == 'gif') return imagecreatefromgif($filename);
      if(strtolower($extension) == 'bmp') return imagecreatefrombmp($filename);

      return false;
    endif;
  }
}
