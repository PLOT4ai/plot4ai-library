<?php
/**
 * Class containing properties with all the dimenions for the different print sizes
 */
class PDF_Size
{
  public $widthMm;
  public $heightMm;
  public $format;
  public $marginLeft;
  public $marginRight;
  public $marginTop;
  public $marginBottom;
  public $listIndentWidth = 5;
  public $introPageWidthFactor;

  /* footer */
  public $footerOffsetX;
  public $footerOffsetY;
  public $footerAddXAI;
  public $footerAddXLogoSub;
  public $logoSubHeight;

  /* page 1 */
  public $page1AddXPic;

  /* page 2*/
  public $phasePic;
  public $tableMaxCell1;
  public $tableMaxCellPadding;
  public $tablePhaseCell1;

  /* tables */
  public $tableMax;
  public $tablePhase;

  /* card colours */
  public $frontHeaderHeight1;
  public $frontExplanationYPos;
  public $frontExplanationYFromBottom;
  public $frontDarkYPos1;
  public $frontDarkYPos2;

  /* card front */
  public $frontYInit;
  public $frontY2Init;
  public $frontYWhileCheck;
  public $fontSizeIncrease;

  /* font sizes */
  public $fontFooterPlot;
  public $fontFooterAI;
  public $fontNormal;
  public $fontNormalPlus;
  public $fontFrontQuestionInit;
}
class PDF_Size_A4 extends PDF_Size
{
  public function __construct() {
    $this->format = "A4";
    $this->widthMm = 210;
    $this->heightMm = 297;

    $this->marginLeft = 15;
    $this->marginRight = 15;
    $this->marginTop = 27;
    $this->marginBottom = 25;

    $this->introPageWidthFactor = 0.75;

    /* footer */
    $this->footerOffsetX = 83;
    $this->footerOffsetY = 282;
    $this->footerAddXAI = 25;
    $this->footerAddYAI = 3;
    $this->footerAddXLogoSub = 32;
    $this->logoSubHeight = 8;

    /* page 1 */
    $this->page1AddXPic = 50;

    /* page 2*/
    $this->categoryPic = 50;
    $this->phasePic = 50;
    $this->tableMaxCell1 = 150;
    $this->tableMaxCellPadding = 8;
    $this->tablePhaseCell1 = 75;

    /* tables */
    $this->tableMax = 800;
    $this->tablePhase = 200;

    /* card colours */
    $this->frontHeaderHeight = 30;
    $this->frontExplanationYPos = 72;
    $this->frontExplanationYFromBottom = 60;
    $this->frontDarkYPos1 = 240;
    $this->frontDarkYPos2 = 280;

    /* icons */
    $this->iconCatX = 8;
    $this->iconCatY = 7;
    $this->iconCatWidth = 17;
    $this->iconCatMargin = 5;
    $this->iconPhaseX = 185;

    /* card front */
    $this->frontQuestionYInit = 35;
    $this->frontQuestionY2Init = 75;
    $this->frontQuestionYWhileCheck = 72;
    $this->fontQuestionSizeIncrease = 1;
    $this->frontExplanationY = 70;
    $this->frontExplanationMargin = 28;
    $this->frontExplanationY2Init = 250;
    $this->frontExplanationYWhileCheck = 240;
    $this->fontExplanationSizeIncrease = 0.5;
    $this->frontThreatIfY1 = 250;
    $this->frontThreatIfY2 = 260;
    $this->frontTextPicSize = 15;
    $this->frontTextPicX = 7;
    $this->frontExclamationPicYPos = 252;
    $this->backRecommendationY = 74;
    $this->backQrX = 100;
    $this->backQrY = 240;
    $this->backQrXPos = 160;
    $this->backQrYPos = 237;
    $this->backQrWidth = 40;
    $this->backInfoQrAdjustY = 10;
    $this->backInfoQrWidth = 30;

    /* font sizes */
    $this->fontFooterPlot = 22;
    $this->fontFooterAI = 20;
    $this->fontNormal = 12;
    $this->fontNormalPlus = 14;
    $this->fontH1 = 16;
    $this->fontFrontQuestionInit = 21;
    $this->fontExplanation = 18;
    $this->fontFrontExplanationInit = 17.5;
    $this->fontThreatIf1 = 24;
    $this->fontThreatIf2 = 21;
  }
}
class PDF_Size_A6 extends PDF_Size
{
  public function __construct() {
    /**
     * going to define a custom format instead of A6, so we can have a 3mm excess
     *
     * 'A6'                     => array(  297.638,   419.528)
     * measures are calculated in this way: (inches * 72) or (millimeters * 72 / 25.4)
     */
    $this->format = array(111, 154);
    $this->widthMm = 105;
    $this->heightMm = 148;

    $this->marginLeft = 7.5;
    $this->marginRight = 7.5;
    $this->marginTop = 6;
    $this->marginBottom = 6;

    $this->introPageWidthFactor = 0.80;

    /* footer */
    $this->footerOffsetX = 44.5;
    $this->footerOffsetY = 144;
    $this->footerAddXAI = 12.5;
    $this->footerAddYAI = 2.1;
    $this->footerAddXLogoSub = 16;
    $this->logoSubHeight = 4;

    /* page 1 */
    $this->page1AddXPic = 20;

    /* page 2*/
    $this->categoryPic = 25;
    $this->phasePic = 15;
    $this->tableMaxCell1 = 75;
    $this->tableMaxCellPadding = 4;
    $this->tablePhaseCell1 = 37.5;

    /* tables */
    $this->tableMax = 455;
    $this->tablePhase = 100;

    /* card colours */
    $this->frontHeaderHeight = 15;
    $this->frontExplanationYPos = 36;
    $this->frontExplanationYFromBottom = 30;
    $this->frontDarkYPos1 = 125.5;
    $this->frontDarkYPos2 = 142.5;

    /* icons */
    $this->iconCatX = 5;
    $this->iconCatY = 5;
    $this->iconCatWidth = 8.5;
    $this->iconCatMargin = 2.5;
    $this->iconPhaseX = 97.5;

    /* card front */
    $this->frontQuestionYInit = 17.5;
    $this->frontQuestionY2Init = 37.5;
    $this->frontQuestionYWhileCheck = 36;
    $this->fontQuestionSizeIncrease = 0.5;
    $this->frontExplanationY = 35;
    $this->frontExplanationMargin = 14;
    $this->frontExplanationY2Init = 130;
    $this->frontExplanationYWhileCheck = 125;
    $this->fontExplanationSizeIncrease = 0.25;
    $this->frontThreatIfY1 = 129;
    $this->frontThreatIfY2 = 134;
    $this->frontTextPicSize = 7.5;
    $this->frontTextPicX = 5.5;
    $this->frontExclamationPicYPos = 130;
    $this->backRecommendationY = 37;
    $this->backQrX = 50;
    $this->backQrY = 125.5;
    $this->backQrXPos = 85.5;
    $this->backQrYPos = 120.5;
    $this->backQrWidth = 20;
    $this->backInfoQrAdjustY = 5;
    $this->backInfoQrWidth = 15;

    /* font sizes */
    $this->fontFooterPlot = 11;
    $this->fontFooterAI = 10;
    $this->fontNormal = 7;
    $this->fontNormalPlus = 8;
    $this->fontH1 = 9;
    $this->fontFrontQuestionInit = 10.5;
    $this->fontExplanation = 9;
    $this->fontFrontExplanationInit = 8.75;
    $this->fontThreatIf1 = 12;
    $this->fontThreatIf2 = 10.5;
  }
}
