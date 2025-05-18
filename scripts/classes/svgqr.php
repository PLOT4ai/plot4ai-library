<?php
require_once "qr.php";

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SvgQR extends QR {

    protected function getOptions() {
      return new QROptions([
        'version'           => 7,
        'outputType'        => QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel'          => QRCode::ECC_L,
        'scale'             => 5,
        'drawLightModules'  => false, // don't draw background modules
        'cssClass'          => 'qr-dark',  // only dark modules will be drawn
        'svgDefs'           => '<style><![CDATA[
            .qr-dark { fill: #000000; }
        ]]></style>',
      ]);
    }

    protected function getFileExtension() {
      return "svg";
    }
}
