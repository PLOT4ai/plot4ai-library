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
    $this->sizeStr = "A4";

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
    $this->frontExplanationYPos = 76;
    $this->frontExplanationYFromBottom = 60;
    $this->frontDarkYPos1 = 256;
    $this->frontDarkYPos2 = 280;

    /* icons */
    $this->iconCatX = 6;
    $this->iconCatY = 5;
    $this->iconCatWidth = 15;
    $this->iconCatMargin = 3;
    $this->iconPhaseX = 185;

    /* background */
    $this->backgroundHeight = 208;
    $this->backgroundHeightAdjustY = 2;

    /* card front */
    $this->frontQuestionYInit = 48;
    $this->frontQuestionY2Init = 75;
    $this->frontQuestionYWhileCheck = 72;
    $this->fontQuestionSizeIncrease = 1;
    $this->frontCategoryYPos = $this->frontHeaderHeight;
    $this->frontCategoryHeight = 14;
    $this->frontCategoryTextY = $this->frontCategoryYPos + 3;
    $this->frontExplanationY = 70;
    $this->frontExplanationMargin = 15;
    $this->frontExplanationY2Init = 250;
    $this->frontExplanationYWhileCheck = 240;
    $this->fontExplanationSizeIncrease = 0.5;
    $this->frontCIATextY = 235;
    $this->frontCIALabelY = 244;
    $this->frontThreatIfY = 263;
    $this->frontTextPicSize = 13;
    $this->frontTextPicX = 10;
    $this->frontExclamationPicYPos = 261;
    $this->backRecommendationHeaderY = 50;
    $this->backRecommendationY = 74;
    $this->backQrX = 100;
    $this->backQrY = 240;
    $this->backQrXPos = 160;
    $this->backQrYPos = 237;
    $this->backQrWidth = 40;
    $this->backInfoQrAdjustY = 10;
    $this->backInfoQrWidth = 30;

    /* CIA values */
    $this->ciaLabelPadding = 4;
    $this->ciaLabelHeight = 10;

    /* font sizes */
    $this->fontFooterPlot = 22;
    $this->fontFooterAI = 20;
    $this->fontNormal = 12;
    $this->fontNormalPlus = 14;
    $this->fontH1 = 16;
    $this->fontCategory = 13;
    $this->fontFrontQuestionInit = 21;
    $this->fontCIAText = 15;
    $this->fontCIALabels = 14;
    $this->fontRecommendationHeader = 21;
    $this->fontExplanation = 18;
    $this->fontFrontExplanationInit = 17.5;
    $this->fontThreatIf = 18;
  }
}
class PDF_Size_A6 extends PDF_Size
{
  public function __construct() {
    $this->sizeStr = "A6";
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
    $this->frontExplanationYPos = 38;
    $this->frontExplanationYFromBottom = 30;
    $this->frontDarkYPos1 = 125.5;
    $this->frontDarkYPos2 = 142.5;

    /* icons */
    $this->iconCatX = 5;
    $this->iconCatY = 2.5;
    $this->iconCatWidth = 8;
    $this->iconCatMargin = 2.5;
    $this->iconPhaseX = 97.5;

    /* background */
    $this->backgroundHeight = 108;
    $this->backgroundHeightAdjustY = 0;

    /* card front */
    $this->frontQuestionYInit = 23.5;
    $this->frontQuestionY2Init = 37.5;
    $this->frontQuestionYWhileCheck = 36;
    $this->fontQuestionSizeIncrease = 0.5;
    $this->frontCategoryYPos = $this->frontHeaderHeight;
    $this->frontCategoryHeight = 7;
    $this->frontCategoryTextY = $this->frontCategoryYPos + 1.5;
    $this->frontExplanationY = 35;
    $this->frontExplanationMargin = 14;
    $this->frontExplanationY2Init = 130;
    $this->frontExplanationYWhileCheck = 125;
    $this->fontExplanationSizeIncrease = 0.25;
    $this->frontCIATextY = 115;
    $this->frontCIALabelY = 119;
    $this->frontThreatIfY = 131;
    $this->frontTextPicSize = 7.5;
    $this->frontTextPicX = 5.5;
    $this->frontExclamationPicYPos = 130;
    $this->backRecommendationHeaderY = 25;
    $this->backRecommendationY = 37;
    $this->backQrX = 50;
    $this->backQrY = 125.5;
    $this->backQrXPos = 85.5;
    $this->backQrYPos = 120.5;
    $this->backQrWidth = 20;
    $this->backInfoQrAdjustY = 5;
    $this->backInfoQrWidth = 15;

    /* CIA values */
    $this->ciaLabelPadding = 2;
    $this->ciaLabelHeight = 5;

    /* font sizes */
    $this->fontFooterPlot = 11;
    $this->fontFooterAI = 10;
    $this->fontNormal = 7;
    $this->fontNormalPlus = 8;
    $this->fontH1 = 9;
    $this->fontCategory = 5.5;
    $this->fontFrontQuestionInit = 9;
    $this->fontCIAText = 7.5;
    $this->fontCIALabels = 7;
    $this->fontExplanation = 9;
    $this->fontFrontExplanationInit = 8.75;
    $this->fontRecommendationHeader = 10.5;
    $this->fontThreatIf = 10;
  }
}
