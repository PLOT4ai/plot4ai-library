<?php
// to facilitate testing the generation of QRs

// load dependencies
require "/app/vendor/autoload.php";

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;

class QR {
  private const QR_IMG_DIR = __DIR__."/../output";
  private const QR_OVERRIDE_CACHE = true;
  private const QR_CONF_ECC_LEVEL = QRCode::ECC_M; // Error correction level M
  private const QR_CONF_SCALE     = 3; // Scale (size multiplier)
  private const QR_CONF_MARGIN    = 4; // Margin (padding around the QR code)
  private const QR_CONF_TYPE      = QRCode::OUTPUT_IMAGE_PNG; // Output as PNG
  // this private variable will be filled with the QR_CONF defaults defined above
  private $qrOptions;

  public function __construct() {
    $this->qrOptions = new QROptions([
      'eccLevel' => self::QR_CONF_ECC_LEVEL,
      'scale'    => self::QR_CONF_SCALE ,
      'margin'   => self::QR_CONF_MARGIN,
      'outputType' => QRCode::OUTPUT_IMAGE_PNG,
      'imageTransparent'    => true,
    ]);
  }

  public function getQr($path) {
    $hash = "567";
    $qrPath = self::QR_IMG_DIR."/".$hash.".png";
    return $qrPath;
  }

  public function getQrPath($threat) {
    $hash = "567";
    $qrPath = self::QR_IMG_DIR."/".$hash.".png";
    $url = "https://plot4.ai/library/card/".$hash."?".time();
    $color = hexdec("ff0000");
      /*---
        $text,
        $outfile = false,
        $level = QR_ECLEVEL_L,
        $size = 3,
        $margin = 4,
        $saveandprint=false,
        $back_color = 0xFFFFFF,
        $fore_color = 0x000000
        */
    //QRcode::png($url, $qrPath, QR_ECLEVEL_M, 3, 4, false, $color);
    $this->generateQRCode($url, $qrPath, $this->qrOptions, $color);

    return $qrPath;
  }
  public function generateQRCode(string $url, string $path, QROptions $options, ?int $color = null) {
    if ($color !== null) {
        $rgbColor = $this->intToRgb($color);
        /*
        $options->moduleValues = [
            0 => [60, 0, 0], // Background color (white)
            1 => $rgbColor,          // Foreground color
        ];
        */
        //var_dump($options->moduleValues);
        //$options->bgColor = $rgbColor;
        //$options->imageTransparent = true;
        //$options->transparencyColor = [255, 255, 255];
        //$options->bgColor = $rgbColor;
    }
    //var_dump($options);

    $qrcode = new QRCode($options);

    // Generate and save the QR code
    $qrcode->render($url, $path);
  }
  private function getCardQrPath($question, $qrStr) {
    $filename = "card_qr_".substr(hash('sha256', $question.$qrStr), 0, 12);
    $qrPath = self::QR_IMG_DIR."/".$filename.".png";
    if (!file_exists($qrPath)) {
      //QRcode::png(MarkDown::getLinkUrlOnly($threat->qr), $qrPath, QR_ECLEVEL_M, 3, 4, false);
      $this->generateQRCode(MarkDown::getLinkUrlOnly($qrStr), $qrPath, $this->qrOptions, null);
    }
    return $qrPath;
  }
  // convert an integer (result of hexdec) into an RGB array
  private function intToRgb(int $color) {
    return [
        ($color >> 16) & 0xFF, // Extract the red component
        ($color >> 8) & 0xFF,  // Extract the green component
        $color & 0xFF          // Extract the blue component
    ];
  }

  private function getSvgOptions() {
    $options = new QROptions([
      'version'           => 7,
      'outputType'        => QRCode::OUTPUT_MARKUP_SVG,
      'eccLevel'          => QRCode::ECC_L,
      'scale'             => 20,
      'drawLightModules'  => false, // don't draw background modules
      'svgDefs'           => '<style><![CDATA[
          .qr-dark { fill: #000000; }
      ]]></style>',
      'cssClass'          => 'qr-dark',  // only dark modules will be drawn
    ]);
    return $options;




    $options = new QROptions;

    $options->version              = 7;
    $options->outputInterface      = QRMarkupSVG::class;
    // if set to false, the light modules won't be rendered
    $options->drawLightModules     = true;
    $options->svgUseFillAttributes = true;
    /*
    // draw the modules as circles isntead of squares
    $options->drawCircularModules  = true;
    $options->circleRadius         = 0.4;
    */
    // connect paths to avoid render glitches
    // @see https://github.com/chillerlan/php-qrcode/issues/57
    $options->connectPaths         = true;
    // keep modules of these types as square
    $options->keepAsSquare         = [
    	QRMatrix::M_FINDER_DARK,
    	QRMatrix::M_FINDER_DOT,
    	QRMatrix::M_ALIGNMENT_DARK,
    ];
    $options->bgColour        = '#FF0000';
    $options->transparencyColor = '#FFFFFF';
    $options->imageTransparent    = true;
    //$options->svgOpacity          = 0;
    return $options;
  }

  public function getSvgQr($threat) {
    $hash = "567";
    $qrPath = self::QR_IMG_DIR."/".$hash.".svg";
    $url = "https://plot4.ai/library/card/".$hash."?".time();
    $out  = (new QRCode($this->getSvgOptions()))->render($url, $qrPath); // -> data:image/svg+xml;base64,PD94bWwgdmVyc2...
    return $qrPath;
  }
}

$qr = new QR();
$path = $qr->getSvgQr("blaat");
echo $path;
