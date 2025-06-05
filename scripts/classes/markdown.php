<?php
class Markdown extends Parsedown {
  public function stripLinks($text) {
    return preg_replace("/<a.*>(.*)<\/a>/", "$1", $this->text(trim($this->applyFixes($text))));
  }
  public function convert($text) {
    return str_replace("<a", "<a target=\"_blank\"", $this->text(trim($this->applyFixes($text))));
  }
  public function convertInline($text) {
    return str_replace("<a", "<a target=\"_blank\"", $this->line(trim($this->applyFixes($text))));
  }
  public static function getLinkUrlOnly($mdText) {
    if (preg_match("/\[(.+)\]\((.+)\)/", $mdText, $matches)) {
      if (count($matches) == 3) {
        return $matches[2];
      }
    }
    return false;
  }
  private function fixNestedList($text) {
    return str_replace("\n * ", "\n  * ", str_replace("\n - ", "\n  - ", $text));
  }
  private function fixHtmlChars($text) {
    return str_replace("&", "&amp;", str_replace("\"", "&quot;", $text));
  }
  private function applyFixes($text) {
    return $this->fixNestedList($text);
  }
}
