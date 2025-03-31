<?php
require_once __DIR__.'/markdown.php';
require_once __DIR__.'/pdf_size.php';
#require_once __DIR__.'/../lib/phpqrcode/qrlib.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Class that can generate the card deck in PDF format, based on a json file with all the data
 */
class PlotPDF
{
  /**
   * The PDF library that we use as basis
   */
  private TCPDF $pdf;
  /**
   * A helper class that knows about all the different margins, font-sizes, etc. associated with A4 and A6
   */
  private PDF_Size $size;
  /**
   * Class that can help converting markdown notation to the actual markup
   * The json file contains this in fields such as the description and recommendation
   */
  private Markdown $md;
  /**
   * Each card belongs to a category.
   * This array will hold all categories once the json is parsed
   */
  private $categories;
  /**
   * The print mode determines how the PDF is printed:
   * - frontside and backside
   * - fontsides only
   * - backsides only
   * Depending on how you want to print the cards, you will need this
   */
  private $printMode;
  public const PRINT_MODE_FRONTS = "Fronts";
  public const PRINT_MODE_BACKS = "Backs";
  public const PRINT_MODE_BOTH = "FrontAndBack";
  private array $validPrintModes = array(
    self::PRINT_MODE_FRONTS,
    self::PRINT_MODE_BACKS,
    self::PRINT_MODE_BOTH
  );
  public const FILENAME = "plot4ai-carddeck.pdf";

  private const BASE_PATH_DATA= __DIR__."/../";
  private const IMGS_PATH     = __DIR__."/../img";

  /**
   * constants we use for the QR images
   */
  private const QR_IMG_DIR = __DIR__."/../cache/qr";
  private const QR_OVERRIDE_CACHE = true;
  private const QR_CONF_ECC_LEVEL = QRCode::ECC_M; // Error correction level M
  private const QR_CONF_SCALE     = 3; // Scale (size multiplier)
  private const QR_CONF_MARGIN    = 4; // Margin (padding around the QR code)
  private const QR_CONF_TYPE      = QRCode::OUTPUT_IMAGE_PNG; // Output as PNG
  // this private variable will be filled with the QR_CONF defaults defined above
  private $qrOptions;

  /**
   * constants we use in the PDF
   */
   private const PDF_CREATOR = "Isabel Barbera - plot4ai";
   private const PDF_AUTHOR = "Isabel Barbera";
   private const PDF_TITLE = "PLOT4ai";
   private const PDF_SUBJECT = "Threat Library";
   private const PDF_KEYWORDS = "PLOT4ai, Practical, Threat Modeling, library";

   private $ciaValues = array(
     "c" => "Confidentiality",
     "i" => "Integrity",
     "a" => "Availability"
   );

   /**
    *
    */
  private $categoryColours = array(
    // Data & Data Governance
    "83b3db" => array(
      "main" => "83b3db",
      "light" => "b2cde8",
    ),
    // Transparency & Accessibility
    "7fccdc" => array(
      "main" => "7fccdc",
      "light" => "b2dee9",
    ),
    // Privacy & Data Protection
    "94cfbd" => array(
      "main" => "94cfbd",
      "light" => "bde0d4",
    ),
    // Cybersecurity
    "bdd895" => array(
      "main" => "bdd895",
      "light" => "d5e5bd",
    ),
    // Safety & Environmental Impact
    "f7f09f" => array(
      "main" => "f7f09f",
      "light" => "f8f6c6",
    ),
    // Bias, Fairness & Discrimination
    "f8d18c" => array(
      "main" => "f8d18c",
      "light" => "fae0b5",
    ),
    // Ethics & Human Rights
    "f2bc9a" => array(
      "main" => "f2bc9a",
      "light" => "f6d4bd",
    ),
    // Accountability & Human Oversight
    "eea4b5" => array(
      "main" => "eea4b5",
      "light" => "f5c7d0",
    ),
  );

   /**
    * Constructor,
    * - sets the desired size (A4/A6)
    * - sets the desired printmode
    * - creates an instance that handles the PDF logic
    * -
    */
  public function __construct($size) {
    if ($size == "A4") {
      $this->size = new PDF_Size_A4();
    }
    elseif ($size == "A6") {
      $this->size = new PDF_Size_A6();
    }
    else {
      throw new Exception("invalid size");
    }
    $this->setPrintMode(self::PRINT_MODE_BOTH);
    $this->md = new Markdown();
    $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $this->size->format, true, 'UTF-8', false);
    $this->qrOptions = new QROptions([
      'eccLevel' => self::QR_CONF_ECC_LEVEL,
      'scale'    => self::QR_CONF_SCALE ,
      'margin'   => self::QR_CONF_MARGIN,
      'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    ]);
  }

  public function setCategories($arr) {
    $this->categories = $arr;
  }

  public function getContents() {
    //Close and output PDF document
    $this->pdf->Output(self::FILENAME, 'I');
  }

  public function writeContents($fullPath) {
    //Close and output PDF document
    $this->pdf->Output($fullPath, 'F');
  }

  public function setPrintMode($mode) {
    if (!in_array($mode, $this->validPrintModes)) {
      //throw new Exception("invalid print mode");
      //exit;

      /* don't do anything, so we stick with the default set in the constructor */
    }
    else {
      $this->printMode = $mode;
    }
  }

  private function getDataFromFile($filepath) {
    if ($filepath == false) {
      throw new Exception("realpath returned false for file : [".$filepath."]");
    }
    /*
    if (substr($filepath, 0, strlen(self::BASE_PATH_DATA)) != self::BASE_PATH_DATA) {
      throw new Exception("Invalid path to json file : [".$filepath."]");
    }
    */
    if (!file_exists($filepath)) {
      throw new Exception("[".$filepath."] doesn't exist");
    }
    elseif (!is_readable($filepath)) {
      throw new Exception("file [".$filepath."] exists, but isn't readable");
    }
    if ($data = file_get_contents($filepath)) {
      return $data;
    }
    else {
      throw new Exception("couldn't read data from file : [".$filepath."]");
    }
  }

  public function generatePDFFromJsonFile($file) {
    $categories = array();
    $data = $this->getDataFromFile($file);
    $dataJson = json_decode($data);
    $cards = array();
    $c = 1;
    foreach ($dataJson AS $obj) {
      // create category object, so we can save it in our internal array
      $item = new stdClass();
      $item->category = $obj->category;
      $item->id = $obj->id;
      $item->colour = $obj->colour;
      $categories[] = $item;
      // add cards to our cards-array
      foreach($obj->cards AS $cObj) {
        $cObj->id = $c++;
        $cards[] = $cObj;
      }
    }
    $this->setCategories($categories);
    $this->generatePDF($cards);
  }
  public function generatePDFFromJsonString($json) {
    $this->generatePDF(json_decode($json));
  }
  public function generatePDF($threats) {
    if (!is_array($this->categories) || count($this->categories) == 0) {
      throw new Exception("You called generatePDF without loading the categories first");
    }
    // set document information
    $this->pdf->SetCreator(self::PDF_CREATOR);
    $this->pdf->SetAuthor(self::PDF_AUTHOR);
    $this->pdf->SetTitle(self::PDF_TITLE." (".$this->size->sizeStr.")");
    $this->pdf->SetSubject(self::PDF_SUBJECT);
    $this->pdf->SetKeywords(self::PDF_KEYWORDS);

    // set default monospaced font
    $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);
    $this->pdf->setListIndentWidth($this->size->listIndentWidth);

    // remove default header/footer
    $this->pdf->setPrintHeader(false);
    $this->pdf->setPrintFooter(false);

    // set auto page breaks
    $this->pdf->SetAutoPageBreak(TRUE, $margin = 0);

    // set image scale factor
    $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $this->pdf->setLanguageArray($l);
    }

    $this->createIntro();

    // ---------------------------------------------------------

    foreach ($threats AS $threat) {
      // reset pointer to the last page
      //$this->pdf->lastPage();
      if (in_array($this->printMode, array(self::PRINT_MODE_FRONTS, self::PRINT_MODE_BOTH))) {
        $this->generateCardFront($threat);
      }
      if (in_array($this->printMode, array(self::PRINT_MODE_BACKS, self::PRINT_MODE_BOTH))) {
        $this->generateCardBack($threat);
      }
    }
  }

  private function createIntro() {
    $width = $this->pdf->getPageWidth() * $this->size->introPageWidthFactor;
    $xPos = ($this->pdf->getPageWidth() - $width) / 2;
    $yPos = $this->pdf->getY() + 10;
    if ($this->printMode == self::PRINT_MODE_FRONTS) {
      $this->page1Front($width, $xPos, $yPos);
      $this->page2Front($width, $xPos, $yPos);
      $this->page3Front($width, $xPos, $yPos);
    }
    elseif ($this->printMode == self::PRINT_MODE_BACKS) {
      $this->page1Back($width, $xPos, $yPos);
      $this->page2Back($width, $xPos, $yPos);
      $this->page3Back($width, $xPos, $yPos);
    }
    elseif ($this->printMode == self::PRINT_MODE_BOTH) {
      $this->page1Front($width, $xPos, $yPos);
      $this->page1Back($width, $xPos, $yPos);
      $this->page2Front($width, $xPos, $yPos);
      $this->page2Back($width, $xPos, $yPos);
      $this->page3Front($width, $xPos, $yPos);
      $this->page3Back($width, $xPos, $yPos);
    }
  }

  private function page1Front($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);

    $this->pdf->setY($this->size->marginTop + 20);

    $src = self::IMGS_PATH."/pdf/phases-and-categories_whitebg_800.png";
    $this->pdf->Image($src, $xPos, $yPos + $this->size->page1AddXPic, $width, '', '', '', '', false, 300);

    $this->generateFooter();
  }
  private function page1Back($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', 12);
    $qrPath = $this->getQr("how-does-it-work?qr&instructions");
    $imgdata  = file_get_contents($qrPath, false);
    $imgWidth = $this->pdf->getPageWidth() * $this->size->introPageWidthFactor;
    $imgWidth = $width;
    $this->pdf->Image("@".$imgdata, $xPos, $yPos + $this->size->page1AddXPic, $imgWidth, '', '', '', '', false, 300);
    $this->generateFooter();
  }
  private function page2Front($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $imgWidth = $this->size->categoryPic;
    $firstCellStyle = "width: ".$this->size->tableMaxCell1."; border-bottom: 1px solid black; text-align: center;";
    $secondCellStyle = "border-bottom: 1px solid black; font-size: ".$this->size->fontNormalPlus."pt; text-align: justify";
    $table = '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">Guidelines</h1>'.
      '<p style="text-align: justify;">PLOT4ai is a library containing 86 threats classified under the following <strong>8 categories:</strong></p>'.
      '<table width="'.$this->size->tableMax.'" cellspacing="0" cellpadding="'.$this->size->tableMaxCellPadding.'">
            <tbody>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/technique.png" width="'.$imgWidth.'px"><br>Technique &amp; Processes</td>
                <td style="'.$secondCellStyle.'">Our processes and/or technical actions can have an adverse impact on individuals or cause harm</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/accessibility.png" width="'.$imgWidth.'px"><br>Accessibility</td>
                <td style="'.$secondCellStyle.'">We are not providing the ability to access and use our AI systems considering all types of individuals</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/identifiability.png" width="'.$imgWidth.'px"><br>Identifiability &amp; Linkability</td>
                <td style="'.$secondCellStyle.'">Individuals can be linked to certain attributes or individuals and they can also be identified</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/security.png" width="'.$imgWidth.'px"><br>Security</td>
                <td style="'.$secondCellStyle.'">We can cause harm or have an adverse impact on individuals by not protecting our AI systems and processes from security threats</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/safety.png" width="'.$imgWidth.'px"><br>Safety</td>
                <td style="'.$secondCellStyle.'">We do not recognize hazards and protect individuals from harms or other dangers</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/unawareness.png" width="'.$imgWidth.'px"><br>Unawareness</td>
                <td style="'.$secondCellStyle.'">We do not inform individuals and offer them the possibility to intervene</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/ethics.png" width="'.$imgWidth.'px"><br>Ethics &amp;<br>Human Rights</td>
                <td style="'.$secondCellStyle.'">We do not reflect on matters of value and principles that can have an adverse impact on individuals or cause harm</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/pdf/non-compliance.png" width="'.$imgWidth.'px"><br>Non-compliance</td>
                <td style="'.$secondCellStyle.'">We do not comply with data protection law and/or other related regulations</td>
              </tr>
            </tbody>
          </table>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($table, true, 0, true, true);
    $this->generateFooter();
  }
  private function page2Back($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $firstCellStyle = "width: ".$this->size->tablePhaseCell1."; border-bottom: 1px solid black; border-right: 1px solid black; padding: 135px; text-align: center;";
    $secondCellStyle = "border-bottom: 1px solid black; padding: 5px; font-size: ".$this->size->fontH1."pt;";
    $table = '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">Development Lifecycle (DLC)</h1>'.
      '<p>PLOT4ai contains a set of only <strong>4 DLC</strong> phases where threats can apply:</p>'.
      '<table><tr><td style="width:30%"></td><td style="width:50%">
        <table width="'.$this->size->tablePhase.'" cellspacing="0" cellpadding="4">
            <tbody>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/icons/lifecycle/design.png" width="'.$this->size->phasePic.'px"></td>
                <td style="'.$secondCellStyle.'"><div style="line-height:16px;">Design</div></td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/icons/lifecycle/input.png" width="'.$this->size->phasePic.'px"></td>
                <td style="'.$secondCellStyle.'"><div style="line-height:16px;">Input</div></td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/icons/lifecycle/model.png" width="'.$this->size->phasePic.'px"></td>
                <td style="'.$secondCellStyle.'"><div style="line-height:16px;">Model</div></td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="'.self::IMGS_PATH.'/icons/lifecycle/output.png" width="'.$this->size->phasePic.'px"></td>
                <td style="'.$secondCellStyle.'"><div style="line-height:16px;">Output</div></td>
              </tr>
            </tbody>
          </table>
        </td><td style="width:20%"></td></tr></table><br><br>'.
        '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">How can you apply PLOT4ai in practice?</h1>'.
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Quick tips before starting:</h2><ul>'.
          '<li style="text-align: justifyt;">Sessions should not be longer than 1.5, max. 2 hours to avoid tiredness and lack of focus. You can also do 30 min. timeboxed sessions focussing on just one or two specific categories.</li>'.
          '<li style="text-align: justifyt;">It is important to identify all the relevant stakeholders that need to be present in the session. Especially during the design phase it is recommended that you involve all the people that have the knowledge and/or can take decisions. Remember that diversity is very important!</li>'.
          '<li style="text-align: justifyt;">A facilitator is needed to guide the sessions. Decide who will be taking this role. It does not have to be a privacy expert but having some knowledge can be helpful.</li>'.
          '<li style="text-align: justifyt;">Preparing for the session by selecting the right questions is very important. For instance, after the design phase, once the requirements are (more) clear, try to avoid selecting cards related to threats that are already taken care of during your quality assurance and control process. Not doing this will otherwise feel like a duplication of work and create frustrations.</li>'.
          '<li style="text-align: justifyt;">If prioritization of the threats is important, consider adding an extra column for Effort in the Threat Report Template (see step 8 below). The priority can then also take into account the effort that is required.</li>'.
          '<li style="text-align: justifyt;">This is like a game. Establish clear rules for time boxing: how long can discussions last per threat and when is an exception allowed.</li>'.
          '</ul>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($table, true, 0, true, true);
    $this->generateFooter();
  }
  private function page3Front($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $html =
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Steps:</h2><ol>'.
          '<li style="text-align: justifyt;">Gather a group of stakeholders to create a Data Flow Diagram (DFD) of the system and interaction elements you want to analyse. A simple representation of the way the data might be flowing can be sufficient during the design phase. You could even jump into the threat modeling session without a DFD; depending on the use-case it is not always essential to have one.</li>'.
          '<li style="text-align: justifyt;">Select cards for the session; you can randomly pick them or focus on a specific category. See also the Quick Tips.</li>'.
          '<li style="text-align: justifyt;">With or without DFD, gather all the important stakeholders - now is when the actual threat modeling session will start.</li>'.
          '<li style="text-align: justifyt;">For each selected card, read out loud the question and the extra info provided on the card.</li>'.
          '<li style="text-align: justifyt;">Discuss the possible threat together. Time box how long you want to think about an answer: 2 minutes per answer can be sufficient but consider accepting exceptions if extra time is required because the group finds it difficult to reach consensus.<br>When threat modeling the category Ethics & Human Rights consider giving more time per question. This category usually asks for a higher level of reflection in the group.</li>'.
          '<li style="text-align: justifyt;">The card will indicate if answering YES or NO to the main question means that you have found a threat.<br>If you are not sure, then it is always a possibility that you have found a threat.</li>'.
          '<li style="text-align: justifyt;">If you have found a threat, turn the card to read the recommendations. This is optional, you can also decide to do that after the session.</li>'.
          '<li style="text-align: justifyt;">Document the threat. You can use the Threat Report Template that we provide for that.</li>'.
          '<li style="text-align: justifyt;">Mark the question as a threat in the file and quickly discuss with the group if the threat should be classified as a Low, Medium or High risk. This is helpful to prioritize actions. You can also take the opportunity to write down some notes about possible actions and even indicate a (risk) owner.<br><br><img src="'.self::IMGS_PATH.'/threat-report.png"><br></li>'.
          '<li style="text-align: justifyt;">You are finished when time is over or when all cards are examined.</li>'.
          '</ol>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->generateFooter();
  }
  private function page3Back($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $html =
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Next Steps:</h2><ul>'.
          '<li style="text-align: justifyt;"i>Threats can also be added to your project backlog (in Jira for instance).</li>'.
          '<li style="text-align: justifyt;">You can decide to focus on easy/quick fixes first and later follow up on the rest.</li>'.
          '<li style="text-align: justifyt;">You will find threats that can be considered like a warning, but that are not really risks yet that you can mitigate at that moment. It is also important to document these threats and review them regularly.</li>'.
          '<li style="text-align: justifyt;">Consider establishing (privacy) acceptance criteria within your development team(s).</li>'.
          '<li style="text-align: justifyt;">In Agile: you can do privacy refinements to go through all the privacy user stories in the backlog.</li>'.
          '<li style="text-align: justifyt;">You can train your team in knowledge areas such as privacy, data protection and ethics. This can also be beneficial to facilitate the threat modeling sessions.</li>'.
          '</ul>';
    $html .=
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Benefits:</h2><ul>'.
          '<li style="text-align: justifyt;">Organisations can benefit from the fact that some of the threats play a more global role what will lead to a consequent improvement of processes. That is why it is important to register the threats and have an overview of what has been mitigated already. This can also be useful for KPI reporting.</li>'.
          '<li style="text-align: justifyt;">Another clear benefit is the reduction of rework: simply because the purpose is more clear and expectations regarding issues like bias, discrimination or explainability can be better managed.</li>'.
          '<li style="text-align: justifyt;">The threat modeling sessions also bring all stakeholders on the same page, reducing time spent in endless discussions.</li>'.
          '<li style="text-align: justifyt;">The output of the sessions can be also used in your (Data) Privacy Impact Assessments, what also saves time.</li>'.
          '<li style="text-align: justifyt;">It brings structure and focus to the teams, increases knowledge and collaboration.</li>'.
          '</ul>';
    $html .=
      '<div style="height: 30px;"> </div><p><i style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">&quot;By applying privacy threat modeling to AI/ML we have learned to humanize the machine. '.
      'The combination of human and machine learning is clearly beneficial for the creation of safe, respectful and privacy friendly products.&quot;</i> PLOT4ai</p>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->generateFooter();
  }

  private function generateFooter() {
    $this->pdf->Image(self::IMGS_PATH.'/pdf/logo-sub.png', $this->size->footerOffsetX+$this->size->footerAddXLogoSub, $this->size->footerOffsetY, '', $this->size->logoSubHeight, '', '', '', false, 300);
    $this->pdf->SetFont('helvetica', 'B', $this->size->fontFooterPlot);

    $this->pdf->SetXY($this->size->footerOffsetX, $this->size->footerOffsetY+1);
    $html = "<span style=\"color: #666;\">PLOT</span><span style=\"color: #999;\">4</span>";
    $this->pdf->writeHTML($html, true, 0, true, true);

    $this->pdf->SetXY($this->size->footerOffsetX+$this->size->footerAddXAI, $this->size->footerOffsetY+$this->size->footerAddYAI);
    $this->pdf->SetFont('helvetica', 'B', $this->size->fontFooterAI);
    $html = "<span style=\"color: #666;\">AI</span>";
    $this->pdf->writeHTML($html, true, 0, true, true);

    //$this->drawGutter();
  }

  private function drawGutter() {
    $this->pdf->SetDrawColor(255, 0, 0);/*
    $this->pdf->Line(3, 3, $this->size->widthMm-3, 3);
    $this->pdf->Line($this->size->widthMm-3, 3, $this->size->widthMm-3, $this->size->heightMm-3);
    $this->pdf->Line($this->size->widthMm-3, $this->size->heightMm-3, 3, $this->size->heightMm-3);
    $this->pdf->Line(3, $this->size->heightMm-3, 3, 3);*/
    $this->pdf->Line(3, 3, $this->size->widthMm+3, 3);
    $this->pdf->Line($this->size->widthMm+3, 3, $this->size->widthMm+3, $this->size->heightMm+3);
    $this->pdf->Line($this->size->widthMm+3, $this->size->heightMm+3, 3, $this->size->heightMm+3);
    $this->pdf->Line(3, $this->size->heightMm+3, 3, 3);
  }

  private function generateCardFront($threat) {

    // add a page
    $this->pdf->AddPage();
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);

    // set font
    $this->generateCardColours($threat);

    $this->generateIcons($threat, true);

    $this->pdf->setY($this->size->frontCategoryTextY);
    $this->pdf->SetAlpha(0.5);
    $this->pdf->SetFont('helvetica', '', $this->size->fontCategory);
    $html = '<h2 style="text-align: center;">'.$threat->label.'</h2>';
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->pdf->SetAlpha(1);

    $this->pdf->setY($this->size->frontQuestionYInit);

    // calculating the right font-size so we can stay within the boundaries of the question section
    $fontSize = $this->size->fontFrontQuestionInit;
    $y2 = $this->size->frontQuestionY2Init;
    $c = 0;
    while ($y2 > $this->size->frontQuestionYWhileCheck) {
      $fontSize = $fontSize - $this->size->fontQuestionSizeIncrease;
      $this->pdf->SetFont('helvetica', 'B', $fontSize);
      $html = '<h1 style="text-align: center;">'.$threat->question.'</h1>';
      $this->pdf->startTransaction();
      $this->pdf->writeHTML($html, true, 0, true, true);
      $y2 = $this->pdf->getY();
      $this->pdf = $this->pdf->rollbackTransaction();
      $c++;
      if ($fontSize < 1) {
        break;
      }
    }
    // end of calculating font-size

    // set font
    $this->pdf->SetFont('helvetica', 'B', $fontSize);
    // write the question
    $html = '<h1 style="text-align: center;">'.$threat->question.'</h1>';
    $this->pdf->writeHTML($html, true, 0, true, true);

    $this->pdf->SetFont('helvetica', '', $this->size->fontExplanation);
    $this->pdf->setY($this->size->frontExplanationY);
    //$this->pdf->SetMargins($this->size->marginLeft+30, $this->size->marginTop, $this->size->marginRight);
    $this->pdf->SetLeftMargin($this->size->frontExplanationMargin);
    $y1 = $this->pdf->getY();
    $text = $this->md->stripLinks($threat->explanation);

    // calculating the right font-size so we don't spill over to the next page
    $curPageNo = $this->pdf->PageNo();
    $calcPageNo = $curPageNo + 1;
    $fontSize = $this->size->fontFrontExplanationInit;
    $y2 = $this->size->frontExplanationY2Init;
    $c = 0;
    while ($calcPageNo > $curPageNo || $y2 > $this->size->frontExplanationYWhileCheck) {
      $fontSize = $fontSize - $this->size->fontExplanationSizeIncrease;
      $html =
        '<p style="text-align:justify; font-size: '.$fontSize.'pt;">'.$text.'</p>';
      $this->pdf->startTransaction();
      $this->pdf->writeHTML($html, true, 0, true, true);
      $y2 = $this->pdf->getY();
      $calcPageNo = $this->pdf->PageNo();
      $this->pdf = $this->pdf->rollbackTransaction();
      $c++;
      if ($fontSize < 1) {
        break;
      }
    }
    // end of calculating font-size
    $html =
      '<p style="text-align:justify; font-size: '.$fontSize.'pt;">'.$text.'</p>';
      $this->pdf->writeHTML($html, true, 0, true, true);

    // if applicable, indicate CIA triad
    if (isset($threat->cia) && is_array($threat->cia) && count($threat->cia) > 0) {
      $this->pdf->setY($this->size->frontCIATextY);
      $this->pdf->SetAlpha(0.67);
      $this->pdf->writeHTML('<p style="font-size: '.$this->size->fontCIAText.'pt;">CIA triad impact:<p>', true, 0, true, true);

      $this->pdf->SetAlpha(1);
      $this->pdf->setY($this->size->frontCIALabelY);
      $words = array();
      foreach ($threat->cia AS $i => $ciaKey) {
        $words[] = $this->ciaValues[$ciaKey];
      }
      $this->showCIA($words);
    }
    //$this->pdf->SetLeftMargin($this->size->marginLeft);
    $this->pdf->setY($this->size->frontThreatIfY);
    $html =
      '<p style="text-align:center; font-size: '.$this->size->fontThreatIf.'pt; color: white;">If your answer is <b>'.mb_strtoupper($threat->threatif).'</b> or <b>MAYBE</b>, you might be at risk</p>';
    $this->pdf->writeHTML($html, true, 0, true, true);
    /*$this->pdf->setY($this->size->frontThreatIfY2);
    $html =
      '<p style="text-align:center; font-size: '.$this->size->fontThreatIf2.'pt; color: white;;">If you are <b>not sure</b>, then you might be at risk too</p>';
    $this->pdf->writeHTML($html, true, 0, true, true);
    */

    // draw pictures
    $this->pdf->SetAlpha(0.6);
    $this->pdf->Image(self::IMGS_PATH.'/icons/exclamation_triangle.png', $this->size->frontTextPicX, $this->size->frontExclamationPicYPos, '', $this->size->frontTextPicSize, '', '', '', false, 300);
    $this->pdf->SetAlpha(1);

    $this->generateFooter();
  }


  private function showCIA($words) {
    $padding = $this->size->ciaLabelPadding;
    $height = $this->size->ciaLabelHeight;
    $x = $this->pdf->GetX();
    $y = $this->pdf->GetY();

    $this->pdf->SetFont('helvetica', '', $this->size->fontCIALabels);

    // Loop over each word
    foreach ($words as $word) {
        $word = strtoupper($word);
        $width = $this->pdf->GetStringWidth($word) + ($padding * 2);

        // ---- 1. Draw Transparent Background ----
        $this->pdf->StartTransform(); // optional: group drawing commands
        $this->pdf->SetAlpha(0.2); // transparent white fill
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect($x, $y, $width, $height, 'F'); // fill only
        $this->pdf->StopTransform();

        // ---- 2. Draw Transparent Border ----
        $this->pdf->SetAlpha(0.25, 'Normal', 'Stroke'); // apply alpha only to the stroke
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Rect($x, $y, $width, $height, 'D'); // draw only

        // ---- 3. Draw Opaque Text ----
        $this->pdf->SetAlpha(0.67);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($width, $height, $word, 0, 0, 'C', false);

        $x += $width + 3;
    }
  }

  private function generateCardBack($threat) {
    // add a page
    $this->pdf->AddPage();
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);

    //var_dump($threat);
    $this->generateCardColours($threat, false);

    $this->generateIcons($threat, false);

    // display the label
    $this->pdf->setY($this->size->frontCategoryTextY);
    $this->pdf->SetAlpha(0.5);
    $this->pdf->SetFont('helvetica', '', $this->size->fontCategory);
    $html = '<h2 style="text-align: center;">'.$threat->label.'</h2>';
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->pdf->SetAlpha(1);

    // Rcommendation Header (question at the front)
    $this->pdf->setY($this->size->backRecommendationHeaderY);
    $this->pdf->SetFont('helvetica', 'B', $this->size->fontRecommendationHeader);
    // write the question
    $html = '<h1 style="text-align: center;">Recommendations</h1>';
    $this->pdf->writeHTML($html, true, 0, true, true);

    $this->pdf->SetFont('helvetica', '', $this->size->fontExplanation);
    $this->pdf->setY($this->size->backRecommendationY);
    //$this->pdf->SetMargins($this->size->marginLeft+30, $this->size->marginTop, $this->size->marginRight);
    $this->getQrPath($threat);

    $this->pdf->SetLeftMargin($this->size->frontExplanationMargin);
    $y1 = $this->pdf->getY();
    $text = $this->md->stripLinks($threat->recommendation);

    // calculating the right font-size so we don't spill over to the next page
    $curPageNo = $this->pdf->PageNo();
    $calcPageNo = $curPageNo + 1;
    $fontSize = $this->size->fontFrontExplanationInit;
    $y2 = $this->size->frontExplanationY2Init;
    $c = 0;
    while ($calcPageNo > $curPageNo || $y2 > $this->size->frontExplanationYWhileCheck) {
      $fontSize = $fontSize - $this->size->fontExplanationSizeIncrease;
      $html =
        '<p style="text-align:justify; font-size: '.$fontSize.'pt;">'.$text.'</p>';
      $this->pdf->startTransaction();
      $this->pdf->writeHTML($html, true, 0, true, true);
      $y2 = $this->pdf->getY();
      $calcPageNo = $this->pdf->PageNo();
      $this->pdf = $this->pdf->rollbackTransaction();
      $c++;
      if ($fontSize < 1) {
        break;
      }
    }
    // end of calculating font-size

    $html =
      '<p style="text-align:justify; font-size: '.$fontSize.'pt;">'.$text.'</p>';
    $this->pdf->writeHTML($html, true, 0, true, true);

    // qr from card (at this moment the Human Rights link)
    if (!empty($threat->qr)) {
      $yPos = $this->pdf->getY() - $this->size->backInfoQrAdjustY;
      $imgWidth = $this->size->backInfoQrWidth;
      $xPos = ($this->pdf->getPageWidth() - $imgWidth) / 2;
      $qrPath = $this->getCardQrPath($threat);
      $imgdata  = file_get_contents($qrPath, false);
      $this->pdf->Image("@".$imgdata, $xPos, $yPos, $imgWidth, '', '', '', '', false, 300);
    }

    // no links in the PDF, we generate a qr instead
    $qrPath = $this->getQrPath($threat);
    $this->pdf->setX($this->size->backQrX);
    $this->pdf->setY($this->size->backQrY);
    $imgdata  = file_get_contents($qrPath, false);
    $this->pdf->Image("@".$imgdata, $this->size->backQrXPos, $this->size->backQrYPos, $this->size->backQrWidth, '', '', '', '', false, 300);

    // set core font
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormalPlus);

    $this->generateFooter();

    // output the HTML content
    //$this->pdf->writeHTML($html, true, 0, true, true);

    //$this->pdf->Ln();
  }

  private function generateCardColours($threat, $front = true) {
    $shade = $front ? "main" : "light";
    $mainBgColour = $this->getCategoryColour($threat->categories[0], $shade);
    $explanationColour = $this->getCategoryColour($threat->categories[0], "main");
    // header with icons
    $this->pdf->Rect(0, 0, $this->pdf->getPageWidth(),  $this->size->frontHeaderHeight, 'DF', array("width"=>0),  $mainBgColour);
    // label
    $this->pdf->SetAlpha(0.7);
    $this->pdf->Rect(0, $this->size->frontCategoryYPos, $this->pdf->getPageWidth(), $this->size->frontCategoryHeight, 'DF', array("width"=>0), $mainBgColour);
    $this->pdf->SetAlpha(1);
    if (!$front) {
      // explanation
      $explanationHeaderYPos = ($this->size->frontCategoryYPos + $this->size->frontCategoryHeight);
      $explanationHeaderYHeight = $this->size->frontExplanationYPos - $explanationHeaderYPos;
      $this->pdf->Rect(0, $explanationHeaderYPos, $this->pdf->getPageWidth(), $explanationHeaderYHeight, 'DF', array("width"=>0), $explanationColour);
    }
    // main body of the card with thext
    $this->pdf->Rect(0, $this->size->frontExplanationYPos, $this->pdf->getPageWidth(), ($this->pdf->getPageHeight()-$this->size->frontExplanationYFromBottom), 'DF', array("width"=>0), $mainBgColour);
    // dark banner yes/no
    if ($front) {
      $this->pdf->SetAlpha(0.25);
      $this->pdf->Rect(0, $this->size->frontDarkYPos1, $this->pdf->getPageWidth(), $this->size->frontDarkYPos2, 'DF', array("width"=>0),  array(0, 0, 0));
      $this->pdf->SetAlpha(1);
    }
    // footer
    $this->pdf->Rect(0, $this->size->frontDarkYPos2, $this->pdf->getPageWidth(), ($this->pdf->getPageHeight()-$this->size->frontDarkYPos2), 'DF', array("width"=>0),  array(255, 255, 255));
  }

  private function generateIcons($threat, $isFront) {
    $x = $this->size->iconCatX;
    $y = $this->size->iconCatY;

    $width = $this->size->iconCatWidth;
    $margin = $this->size->iconCatMargin;

    $xPos = $x;
    $yPos = $y;

    // set alpha to semi-transparency
    $this->pdf->SetAlpha(1);

    $i = 0;
    foreach ($threat->categories AS $category) {
      $category_filename = str_replace(" ", "_", str_replace(", ", "-", str_replace(" & ", "-", mb_strtolower($category))));
      $src = self::IMGS_PATH."/icons/categories/".$category_filename.".svg";
      //$this->pdf->Image($src, $xPos, $yPos, $width, '', '', '', '', false, 300);
      $this->pdf->ImageSVG($src, $xPos, $yPos, '', $width, $link='', $align='', $palign='', $border=0, $fitonpage=false);
      if ($i == 0) {
        // this is the main category, so we display the category background image
        $bgSrc = self::IMGS_PATH."/backgrounds/bg_".$category_filename.".svg";
      }
      $xPos += $width + $margin;
      $i++;
    }

    $xPos = $this->size->iconPhaseX;
    // substract the space the icons take, so we can draw them from left to right
    $xPos = $xPos - (($width + $margin) * (count($threat->phases) - 1));

    $this->pdf->SetAlpha(0.6);
    foreach ($threat->phases AS $phase) {
      $fase = mb_strtolower($phase);
      $src = self::IMGS_PATH."/icons/lifecycle/".mb_strtolower($fase).".png";
      $this->pdf->Image($src, $xPos, $yPos, $width, '', '', '', '', false, 300);
      $xPos += $width + $margin;
    }
    $this->pdf->SetAlpha(1);

    $this->pdf->SetX($x);
    $this->pdf->SetY($y + $width + $margin + $margin);

    if ($isFront) {
      // category background image
      $this->pdf->SetAlpha(0.2);
      //$this->pdf->setRTL(true);
      $bgY = $this->size->frontExplanationY+$this->size->backgroundHeightAdjustY;
      $this->pdf->ImageSVG($bgSrc, $x=0, $y=$bgY, $w='', $h=$this->size->backgroundHeight, $link='', $align='', $palign='', $border=0, $fitonpage=false);
      //$this->pdf->setRTL(false);
      $this->pdf->SetAlpha(1);
    }
  }

  private function getQr($path) {
    $hash = $this->createPathHash($path);
    $qrPath = self::QR_IMG_DIR."/".$hash.".png";
    if (!file_exists($qrPath)) {
      $url = "https://plot4.ai/".$path;
      $this->generateQRCode($url, $qrPath, $this->qrOptions, null);
    }
    return $qrPath;
  }
  private function getQrPath($threat) {
    $hash = $this->createCardId($threat->categories[0], $threat->question);
    $qrPath = self::QR_IMG_DIR."/".$hash.".png";
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
      $this->generateQRCode($url, $qrPath, $this->qrOptions, $color);
    }
    return $qrPath;
  }
  private function generateQRCode(string $url, string $path, QROptions $options, ?int $color = null) {
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
        $options->bgColor = $rgbColor;
    }
    //var_dump($options);

    $qrcode = new QRCode($options);

    // Generate and save the QR code
    $qrcode->render($url, $path);
  }
  private function getCardQrPath($threat) {
    $filename = "card_qr_".substr(hash('sha256', $threat->question.$threat->qr), 0, 12);
    $qrPath = self::QR_IMG_DIR."/".$filename.".png";
    if (!file_exists($qrPath)) {
      //QRcode::png(MarkDown::getLinkUrlOnly($threat->qr), $qrPath, QR_ECLEVEL_M, 3, 4, false);
      $this->generateQRCode(MarkDown::getLinkUrlOnly($threat->qr), $qrPath, $this->qrOptions, null);
    }
    return $qrPath;
  }

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
    /*
    echo "Couldn't find category ".$cat."<br>\n";
    var_dump($this->categories);
    exit;
    */
    // category not found, returning black
    return array(0, 0, 0);
  }
  private function getRgbArr($hex) {
    list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
    return array($r, $g, $b);
  }
  // convert an integer (result of hexdec) into an RGB array
  private function intToRgb(int $color) {
    return [
        ($color >> 16) & 0xFF, // Extract the red component
        ($color >> 8) & 0xFF,  // Extract the green component
        $color & 0xFF          // Extract the blue component
    ];
}

  private function createCardId($cat, $question) {
    return "h".substr(hash('sha256', $cat."_".$question), 0, 12);
  }
  private function createPathHash($path) {
    return "path".substr(hash('sha256', $path), 0, 12);
  }
}
