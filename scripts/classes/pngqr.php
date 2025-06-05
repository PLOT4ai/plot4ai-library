<?php
require_once "qr.php";

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PngQR extends QR {

  protected function getOptions() {
    return new QROptions([
      'eccLevel'          => self::QR_CONF_ECC_LEVEL,
      'scale'             => self::QR_CONF_SCALE ,
      'margin'            => self::QR_CONF_MARGIN,
      'outputType'        => QRCode::OUTPUT_IMAGE_PNG
    ]);
  }

  protected function getFileExtension() {
    return "png";
  }
}
