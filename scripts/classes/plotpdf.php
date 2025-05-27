<?php
require_once __DIR__.'/markdown.php';
require_once __DIR__.'/pdf_size.php';
require_once __DIR__.'/pngqr.php';
require_once __DIR__.'/svgqr.php';

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
   * constants we use in the PDF
   */
   private const PDF_CREATOR = "Isabel Barbera - PLOT4AI";
   private const PDF_AUTHOR = "Isabel Barbera";
   private const PDF_TITLE = "PLOT4AI";
   private const PDF_SUBJECT = "Threat Library";
   private const PDF_KEYWORDS = "PLOT4AI, Practical, Threat Modeling, library";

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

    $this->createIntro($threats);

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

  private function createIntro($threats) {
    $width = $this->pdf->getPageWidth() * $this->size->introPageWidthFactor;
    $xPos = ($this->pdf->getPageWidth() - $width) / 2;
    $yPos = $this->pdf->getY() + 10;
    if ($this->printMode == self::PRINT_MODE_FRONTS) {
      $this->page1Front($width, $xPos, $yPos);
      $this->page2Front($width, $xPos, $yPos, $threats);
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
      $this->page2Front($width, $xPos, $yPos, $threats);
      $this->page2Back($width, $xPos, $yPos);
      $this->page3Front($width, $xPos, $yPos);
      $this->page3Back($width, $xPos, $yPos);
    }
  }

  private function page1Front($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);

    $this->pdf->setY($this->size->marginTop + 20);

    $src = self::IMGS_PATH."/pdf/phases-and-categories.png";
    $this->pdf->Image($src, $xPos, $yPos + $this->size->page1AddXPic, $width, '', '', '', '', false, 300);

    $this->generateNeutralFooter();
  }
  private function page1Back($width, $xPos, $yPos) {
    $qr = new PngQr($this->categories, $this->categoryColours);
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', 12);
    $qrPath = $qr->getQr("how-does-it-work?qr&instructions");
    $imgdata  = file_get_contents($qrPath, false);
    $imgWidth = $this->pdf->getPageWidth() * $this->size->introPageWidthFactor;
    $imgWidth = $width;
    $this->pdf->Image("@".$imgdata, $xPos, $yPos + $this->size->page1AddXPic, $imgWidth, '', '', '', '', false, 300);
    //$this->pdf->ImageSVG("@".$imgdata, $xPos, $yPos + $this->size->page1AddXPic, '', $imgWidth, $link='', $align='', $palign='', $border=0, $fitonpage=false);
    $this->generateNeutralFooter();
  }
  private function page2Front($width, $xPos, $yPos, $threats) {
    $catPath = self::IMGS_PATH.'/icons/categories/';
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', ($this->size->fontNormal-1));
    $imgWidth = $this->size->categoryPic;
    $firstCellStyle = "width: ".$this->size->tableMaxCell1."; border-bottom: 1px solid black; text-align: center;";
    $secondCellStyle = "border-bottom: 1px solid black; font-size: ".($this->size->fontNormalPlus-1)."pt; text-align: justify";
    $table = '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">Guidelines</h1>'.
      '<p style="text-align: justify;">PLOT4AI is a library containing '.count($threats).' threats classified under the following <strong>8 categories:</strong></p>'.
      '<table width="'.$this->size->tableMax.'" cellspacing="0" cellpadding="'.$this->size->tableMaxCellPadding.'">
            <tbody>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/data-data_governance.png')).'" width="'.$imgWidth.'px"><br>Data &amp; Data Governance</td>
                <td style="'.$secondCellStyle.'">Inadequate management and quality of the data used in your AI system, leading to inaccurate and harmful outputs.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/transparency-accessibility.png')).'" width="'.$imgWidth.'px"><br>Transparency &amp; Accessibility</td>
                <td style="'.$secondCellStyle.'">The AI decisions or interactions are not understandable or accessible to all users, which limits the usability of your AI system and reduces trust.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/privacy-data_protection.png')).'" width="'.$imgWidth.'px"><br>Privacy &amp; Data Protection</td>
                <td style="'.$secondCellStyle.'">Lack of proper personal data protection measures, increasing the risk of unauthorized access, misuse, or legal violations.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/cybersecurity.png')).'" width="'.$imgWidth.'px"><br>Cybersecurity</td>
                <td style="'.$secondCellStyle.'">Insufficient security measures that can lead to data breaches, adversarial attacks, or system manipulation.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/safety-environmental_impact.png')).'" width="'.$imgWidth.'px"><br>Safety &amp; Environmental Impact</td>
                <td style="'.$secondCellStyle.'">Hazards and sources of risks that might cause harm to employees, users, infrastructure, or the environment.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/bias-fairness-discrimination.png')).'" width="'.$imgWidth.'px"><br>Bias, Fairness &amp; Discrimination</td>
                <td style="'.$secondCellStyle.'">Presence of bias in the data or design of the AI system, leading to unfair treatment of individuals or groups.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/ethics-human_rights.png')).'" width="'.$imgWidth.'px"><br>Ethics &amp; Human Rights</td>
                <td style="'.$secondCellStyle.'">Overlooking the ethical and societal impact of the AI system, which could result in interference with one or more human rights or lead to unintended harm.</td>
              </tr>
              <tr>
                <td style="'.$firstCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($catPath.'/accountability-human_oversight.png')).'" width="'.$imgWidth.'px"><br>Accountability &amp; Human Oversight</td>
                <td style="'.$secondCellStyle.'">Unclear responsibility for decisions of the AI system and lack of mechanisms for human oversight, increasing the risk of unintended consequences.</td>
              </tr>
            </tbody>
          </table>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($table, true, 0, true, true);
    $this->generateNeutralFooter();
  }
  private function page2Back($width, $xPos, $yPos) {
    $lcPath = self::IMGS_PATH.'/icons/lifecycle';
    $logoPath = self::IMGS_PATH.'/pdf';
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $firstCellStyle = "width: ".$this->size->tablePhaseCell1."; border-bottom: 1px solid black; border-right: 1px solid black; padding: 135px; text-align: center;";
    $secondCellStyle = "border-bottom: 1px solid black; padding: 5px; font-size: ".$this->size->fontH1."pt;";
    $cellStyle = "width: 16.67%; text-align: center;";
    $logoCellStyle = "width: 50%; text-align: center;";
    $table = '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">AI Lifecycle</h1>'.
      '<p>PLOT4AI includes six lifecycle phases where threats may arise.<br>'.
      'Each threat card is classified according to the phase(s) it applies to, helping you focus on the most relevant risks at each stage.</p>'.
      '<table><tr><td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/design.png')).'" width="'.$this->size->phasePic.'px"></td>'.
      '<td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/input.png')).'" width="'.$this->size->phasePic.'px"></td>'.
      '<td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/model.png')).'" width="'.$this->size->phasePic.'px"></td>'.
      '<td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/output.png')).'" width="'.$this->size->phasePic.'px"></td>'.
      '<td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/deploy.png')).'" width="'.$this->size->phasePic.'px"></td>'.
      '<td style="'.$cellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($lcPath.'/monitor.png')).'" width="'.$this->size->phasePic.'px"></td></tr></table><br>'.
      '<p>On the back of each card, you\'ll also find whether the threat is particularly relevant for AI providers, deployers, or both.</p>'.
      '<p>Cards that apply only to Generative AI are marked with the PLOT4GenAI logo at the bottom on both the front and back. All other cards apply to both traditional AI and Generative AI systems.</p>'.
      '<table><tr><td style="'.$logoCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($logoPath.'/plot4ai_black.png')).'" width="'.$this->size->logoPic.'px"></td>'.
      '<td style="'.$logoCellStyle.'"><img src="data:image/png;base64,'.base64_encode(file_get_contents($logoPath.'/plot4genai_black.png')).'" width="'.$this->size->logoPic.'px"></td></tr></table><br><br>'.
        '<h1 style="font-size: '.$this->size->fontH1.'pt; text-align: center; color: #0f71d4;">How can you apply PLOT4AI in practice?</h1>'.
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Quick tips before starting:</h2><ul>'.
          '<li style="text-align: justifyt;"><b>Keep sessions short</b>: Limit sessions to 1.5 hours (2 hours max) to avoid tiredness and lack of focus. You can also run 30-minute timeboxed sessions focusing on one or two categories.</li>'.
          '<li style="text-align: justifyt;"><b>Involve the right stakeholders</b>: Especially during the design phase, include people with the necessary knowledge and decision-making power. Diversity is key!</li>'.
          '<li style="text-align: justifyt;"><b>Assign a facilitator</b>: Choose someone to guide the session. They don’t need to be an AI expert, but some familiarity helps.</li>'.
          '<li style="text-align: justifyt;"><b>Prepare by selecting the right questions</b>: After the design phase, avoid cards covering threats already addressed in QA processes—this prevents duplication and frustration.</li>'.
          '<li style="text-align: justifyt;"><b>Support prioritization</b>: If needed, add an "Effort" column to the Threat Report Template (see Step 8) so prioritization considers both impact and effort.</li>'.
          '<li style="text-align: justifyt;"><b>Set clear rules</b>: Treat it like a game: define how long each threat can be discussed and when exceptions are allowed.</li>'.
          '</ul>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($table, true, 0, true, true);
    $this->generateNeutralFooter();
  }
  private function page3Front($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $html =
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Steps</h2><ol>'.
          '<li style="text-align: justifyt;">Create a Data Flow Diagram (DFD) with key stakeholders to map how data flows in the system. A simple version is fine in the design phase.<br>In some cases, you can skip the DFD entirely and start directly with the threat modeling.</li>'.
          '<li style="text-align: justifyt;">Select cards for the session, either randomly or by focusing on specific categories (see Quick Tips).</li>'.
          '<li style="text-align: justifyt;">Start the session with all key stakeholders—whether or not you’ve prepared a DFD.</li>'.
          '<li style="text-align: justifyt;">Read each selected card aloud, including the extra information provided.<br>For the Ethics &amp; Human Rights category, consider giving more time, as it often requires deeper reflection.</li>'.
          '<li style="text-align: justifyt;">Discuss the potential threat: Timebox the discussion. 2 minutes per threat is usually enough, but allow exceptions if consensus is hard to reach.<br>For the Ethics &amp; Human Rights category, consider giving more time, as it often requires deeper reflection.</li>'.
          '<li style="text-align: justifyt;">Determine whether it’s a threat. The card will say whether a YES or NO answer signals a threat.<br>If you’re unsure, treat it as a potential threat.</li>'.
          '<li style="text-align: justifyt;">Check the recommendations on the back of the card if a threat is found. This can also be done after the session.</li>'.
          '<li style="text-align: justifyt;">Document the threat using the provided Threat Report Template.</li>'.
          '<li style="text-align: justifyt;">Classify the risk as Low, Medium, or High with the group. This helps prioritize follow-up actions. You can also note possible mitigations and assign a (risk) owner.</li>'.
          '<li style="text-align: justifyt;">Finish when time runs out or all cards have been discussed.<br>'./*
          '<br><img src="data:image/png;base64,'.base64_encode(file_get_contents(self::IMGS_PATH.'/threat-report.png')).'"><br></li>'.
          '<li style="text-align: justifyt;">You are finished when time is over or when all cards have been examined.</li>'.*/
          '</ol>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->generateNeutralFooter();
  }
  private function page3Back($width, $xPos, $yPos) {
    $this->pdf->AddPage();
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormal);
    $html =
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Next Steps</h2><ul>'.
          '<li style="text-align: justifyt;">Add threats to your project backlog (e.g. in Jira).</li>'.
          '<li style="text-align: justifyt;">You can choose to tackle quick wins first and address more complex issues later.</li>'.
          '<li style="text-align: justifyt;">Track early warnings. Some threats may not be immediate risks but should still be documented and reviewed regularly.</li>'.
          '<li style="text-align: justifyt;">Follow up using your risk management process. Continue assessing and evaluating threats according to your organization’s procedures.</li>'.
          '<li style="text-align: justifyt;">Invest in training. Building team knowledge in areas like privacy, data protection, and ethics can strengthen both your risk response and future threat modeling sessions.</li>'.
          '</ul>';
    $html .=
        '<h2 style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">Benefits</h2><ul>'.
          '<li style="text-align: justifyt;">Improved processes. Some threats highlight broader, systemic issues, leading to better overall processes. Keeping track of mitigated threats also supports KPI reporting.</li>'.
          '<li style="text-align: justifyt;">Less rework. With clearer goals and expectations around topics like bias, discrimination, and explainability, teams can reduce misunderstandings and avoid redundant work.</li>'.
          '<li style="text-align: justifyt;">Aligned stakeholders. Threat modeling sessions help get everyone on the same page, reducing the need for prolonged discussions later.</li>'.
          '<li style="text-align: justifyt;">Support for (Data) Privacy Impact Assessments. Session outputs can feed directly into DPIAs, saving time and effort.</li>'.
          '<li style="text-align: justifyt;">Stronger teams. The process builds structure, focus, shared knowledge, and collaboration.</li>'.
          '</ul>';
    $html .=
      '<div style="height: 30px;"> </div><p><i style="font-size: '.$this->size->fontNormalPlus.'pt; color: #0f71d4;">'.
        'So, why does PLOT4AI work? Because it\'s practical. It simplifies risk identification, saving you time and effort. It helps you stay compliant with regulations; it improves your processes by addressing risks early and systematically. '.
        'And it fosters collaboration, ensuring that everyone—from developers to policymakers—is on the same page.<br><br>'.
        'In a world where AI risks are becoming more complex and subtle, PLOT4AI gives you a clear, structured way to identify them.</i></p>';
    $this->pdf->SetLeftMargin($xPos);
    $this->pdf->SetRightMargin($xPos);
    $this->pdf->writeHTML($html, true, 0, true, true);
    $this->generateNeutralFooter();
  }

  private function generateNeutralFooter() {
    $this->pdf->Image(self::IMGS_PATH.'/pdf/plot4ai_black.png', $x='', $y=$this->size->footerOffsetY, $w='', $h=$this->size->logoHeight, $type='PNG', $link='', $align='', $resize=false, $dpi=300, $palign='C', $ismask=false,
      $imgmask=false, $borde=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs = array());
  }
  private function generateFooter($threat, $side) {
    $this->pdf->SetAlpha(1);
    $imgPath = self::IMGS_PATH.'/pdf/plot4ai_black.png';
    if (isset($threat->aitypes)) {
      if ($side == "front" && !in_array("Traditional", $threat->aitypes)) {
        $imgPath = self::IMGS_PATH.'/pdf/plot4genai_black.png';
      }
      elseif ($side == "back" && in_array("Generative", $threat->aitypes)) {
        $imgPath = self::IMGS_PATH.'/pdf/plot4genai_black.png';
      }
    }
    else {
      echo "threat without ai-types:\n";
      var_dump($threat);
    }
    $this->pdf->Image($imgPath, $x='', $y=$this->size->footerOffsetY, $w='', $h=$this->size->logoHeight, $type='PNG', $link='', $align='', $resize=false, $dpi=300, $palign='C', $ismask=false,
      $imgmask=false, $borde=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs = array());
  }

  private function drawGutter() {
    $this->pdf->SetDrawColor(255, 0, 0);
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
      $this->showTags($words);
    }
    //$this->pdf->SetLeftMargin($this->size->marginLeft);
    $this->pdf->setY($this->size->frontThreatIfY);
    $html =
      '<p style="text-align:center; font-size: '.$this->size->fontThreatIf.'pt; color: white;">If your answer is <b>'.mb_strtoupper($threat->threatif).'</b> or <b>MAYBE</b>, you might be at risk</p>';
    $this->pdf->writeHTML($html, true, 0, true, true);

    // draw pictures
    $this->pdf->SetAlpha(0.6);
    $this->pdf->Image(self::IMGS_PATH.'/icons/exclamation_triangle.png', $this->size->frontTextPicX, $this->size->frontExclamationPicYPos, '', $this->size->frontTextPicSize, '', '', '', false, 300);
    $this->pdf->SetAlpha(1);

    $this->generateFooter($threat, "front");
  }

  private function showTags($words) {
    $padding = $this->size->tagLabelPadding;
    $height = $this->size->tagLabelHeight;
    $x = $this->pdf->GetX();
    $y = $this->pdf->GetY();

    $this->pdf->SetFont('helvetica', '', $this->size->fontTagLabels);

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
    $qr = new SvgQr($this->categories, $this->categoryColours);

    $this->pdf->AddPage();
    $this->pdf->SetMargins($this->size->marginLeft, $this->size->marginTop, $this->size->marginRight);

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
    $qr->getQrPath($threat);

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
    /**
    if (!empty($threat->qr)) {
      $yPos = $this->pdf->getY() - $this->size->backInfoQrAdjustY;
      $imgWidth = $this->size->backInfoQrWidth;
      $xPos = ($this->pdf->getPageWidth() - $imgWidth) / 2;
      $qrPath = $qr->getCardQrPath($threat);
      $imgdata  = file_get_contents($qrPath, false);
      //$this->pdf->Image("@".$imgdata, $xPos, $yPos, $imgWidth, '', '', '', '', false, 300);
      //$this->pdf->ImageSVG("@".$imgdata, $xPos, $yPos, '', $imgWidth, $link='', $align='', $palign='', $border=0, $fitonpage=false);
      $this->pdf->ImageSVG("@".$this->fixSvgContents($imgdata), $xPos, $yPos, '', $imgWidth, $link='', $align='', $palign='', $border=0, $fitonpage=true);
    }
    */

    // if applicable, indicate roles
    if (isset($threat->roles) && is_array($threat->roles) && count($threat->roles) > 0) {
      $this->pdf->setY($this->size->frontRoleTextY);
      $this->pdf->SetAlpha(0.67);
      $this->pdf->writeHTML('<p style="font-size: '.$this->size->fontRoleText.'pt;">Might be applicable to the following roles:<p>', true, 0, true, true);

      $this->pdf->SetAlpha(1);
      $this->pdf->setY($this->size->frontRoleLabelY);
      $roles = array();
      foreach ($threat->roles AS $i => $role) {
        $roles[] = $role;
      }
      $this->showTags($roles);
    }

    // no links in the PDF, we generate a qr instead
    $qrPath = $qr->getQrPath($threat);
    $this->pdf->setX($this->size->backQrX);
    $this->pdf->setY($this->size->backQrY);
    $imgdata  = file_get_contents($qrPath, false);

    $this->pdf->ImageSVG("@".$this->fixSvgContents($imgdata), $this->size->backQrXPos, $this->size->backQrYPos, '', ($this->size->backQrWidth), $link='', $align='', $palign='', $border=0, $fitonpage=true);

    // set core font
    $this->pdf->SetFont('helvetica', '', $this->size->fontNormalPlus);

    $this->generateFooter($threat, "back");
  }

  /**
   * // HACK:
   * the SVQ QR doesn't want to scale, so we need to manually add a width, height & viewport
   * note: changing the width and height doesn't actually work, but seems to be needed for the viewBox to work
   */
  private function fixSvgContents($svgData) {
    $fixedWidth = "100.00mm";
    $fixedHeight = "100.00mm";
    return preg_replace(
      '/<svg([^>]*)viewBox="[^"]*"/',
      //'<svg$1width="' . $fixedWidth . '" height="' . $fixedHeight . '" viewBox="0 0 68.28 90.26"',
      '<svg$1width="' . $fixedWidth . '" height="' . $fixedHeight . '" viewBox="0 0 42 55.520"',
      $svgData
    );
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
    $svgWidthAdjustment = 3;
    $margin = $this->size->iconCatMargin;

    $xPos = $x;
    $yPos = $y;

    // set alpha to semi-transparency
    $this->pdf->SetAlpha(1);

    $i = 0;
    foreach ($threat->categories AS $category) {
      $category_filename = str_replace(" ", "_", str_replace(", ", "-", str_replace(" & ", "-", mb_strtolower($category))));
      $iconPath = self::IMGS_PATH."/icons/categories";
      if ($i > 0) {
        $iconPath .= "/trans";
      }
      $src = $iconPath."/".$category_filename.".svg";
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
      /*
      $src = self::IMGS_PATH."/icons/lifecycle/".mb_strtolower($fase).".png";
      $this->pdf->Image($src, $xPos, $yPos, $width, '', '', '', '', false, 300);
      */
      $src = self::IMGS_PATH."/icons/lifecycle/".mb_strtolower($fase).".svg";
      $this->pdf->ImageSVG($src, $xPos, $yPos, '', $width+$svgWidthAdjustment, $link='', $align='', $palign='', $border=0, $fitonpage=false);
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
  private function getRgbArr($hex) {
    list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
    return array($r, $g, $b);
  }

  private function createCardId($cat, $question) {
    return "h".substr(hash('sha256', $cat."_".$question), 0, 12);
  }
}
