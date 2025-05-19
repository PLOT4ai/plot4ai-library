<?php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

abstract class QR {
  /**
   * constants we use for the QR images
   */
  protected const QR_IMG_DIR = __DIR__."/../cache/qr";
  protected const QR_OVERRIDE_CACHE = true;
  protected const QR_CONF_ECC_LEVEL = QRCode::ECC_M; // Error correction level M
  protected const QR_CONF_SCALE     = 3; // Scale (size multiplier)
  protected const QR_CONF_MARGIN    = 4; // Margin (padding around the QR code)

  // this private variable will be filled with the QR_CONF defaults defined above
  protected $options;

  protected $categories;
  protected $categoryColours;

  abstract protected function getOptions();
  abstract protected function getFileExtension();

  public function __construct($categories, $categoryColours) {
    $this->options          = $this->getOptions();
    $this->categories       = $categories;
    $this->categoryColours  = $categoryColours;
  }

  public function getQr($path) {
    $hash = $this->createPathHash($path);
    $qrPath = self::QR_IMG_DIR."/".$hash.".".$this->getFileExtension();
    if (!file_exists($qrPath)) {
      $url = "https://plot4.ai/".$path;
      $this->generateQRCode($url, $qrPath, null);
    }
    return $qrPath;
  }

  public function getCardQrPath($threat) {
    $filename = "card_qr_".substr(hash('sha256', $threat->question.$threat->qr), 0, 12);
    $qrPath = self::QR_IMG_DIR."/".$filename.".".$this->getFileExtension();
    if (!file_exists($qrPath)) {
      //QRcode::png(MarkDown::getLinkUrlOnly($threat->qr), $qrPath, QR_ECLEVEL_M, 3, 4, false);
      $this->generateQRCode(MarkDown::getLinkUrlOnly($threat->qr), $qrPath, null);
    }
    return $qrPath;
  }
  public function getQrPath($threat) {
    $hash = $this->createCardId($threat->categories[0], $threat->question);
    $qrPath = self::QR_IMG_DIR."/".$hash.".".$this->getFileExtension();
    if (!file_exists($qrPath) || self::QR_OVERRIDE_CACHE) {
      $url = "https://plot4.ai/library/card/".$hash;
      $color = hexdec($this->getCategoryColour($threat->categories[0], "main", false));
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
      $color = null;
      $this->generateQRCode($url, $qrPath, $color);
    }
    return $qrPath;
  }

  protected function generateQRCode(string $url, string $path, ?int $color = null) {
    if ($color !== null) {
        $rgbColor = $this->intToRgb($color);
        $this->options->bgColor = $rgbColor;
    }

    $qrcode = new QRCode($this->options);

    // Generate and save the QR code
    $qrcode->render($url, $path);
  }

  private function createPathHash($path) {
    return "path".substr(hash('sha256', $path), 0, 12);
  }


  /***
   * this should be moved to a static tool class
   */
  // convert an integer (result of hexdec) into an RGB array
  private function intToRgb(int $color) {
    return [
        ($color >> 16) & 0xFF, // Extract the red component
        ($color >> 8) & 0xFF,  // Extract the green component
        $color & 0xFF          // Extract the blue component
    ];
  }
    /***
     * this should be moved to a static tool class
     */
  private function getRgbArr($hex) {
    list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
    return array($r, $g, $b);
  }

  /***
   * this should be moved to a static tool class
   */
  private function createCardId($cat, $question) {
    return "h".substr(hash('sha256', $cat."_".$question), 0, 12);
  }

  /***
   * this should be moved to a static tool class
   */
  private function getCategoryColour($cat, $shade, $asArray = true) {
    foreach ($this->categories AS $catObj) {
      if ($catObj->category == $cat) {
        $catColour = $catObj->colour;
        if (!array_key_exists($catColour, $this->categoryColours)) {
          echo $catColour." doesn't exist in :";
          var_dump($this->categoryColours);
        }
        else {
          $catColours = $this->categoryColours[$catColour];
          if (!array_key_exists($shade, $catColours)) {
            echo "invalid shade. must be one of ".print_r(array_keys($catColours), true);
            exit;
          }
          else {
            if ($asArray) {
              return $this->getRgbArr("#".$catColours[$shade]);
            }
            else {
              return $catColours[$shade];
            }
          }
        }
      }
    }
    // category not found, returning black
    return array(0, 0, 0);
  }
}
