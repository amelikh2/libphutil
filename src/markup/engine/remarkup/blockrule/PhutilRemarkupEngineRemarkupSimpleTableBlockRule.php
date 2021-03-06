<?php

/**
 * @group markup
 */
final class PhutilRemarkupEngineRemarkupSimpleTableBlockRule
  extends PhutilRemarkupEngineBlockRule {

  public function getBlockPattern() {
    return '/^(\|.*+\n?)+$/';
  }

  public function shouldMergeBlocks() {
    return false;
  }

  public function markupText($text) {
    $matches = array();

    $rows = array();
    foreach (explode("\n", $text) as $line) {
      // Ignore ending delimiters.
      $line = rtrim($line, '|');

      preg_match_all('/\|([^|]*)/', $line, $matches);
      $headings = true;
      $cells = array();
      foreach ($matches[1] as $cell) {
        $cell = trim($cell);

        // Cell isn't empty and doesn't look like heading.
        if (!preg_match('/^(|--+)$/', $cell)) {
          $headings = false;
        }
        $cells[] = array('type' => 'td', 'content' => $cell);
      }

      if (!$headings) {
        $rows[] = $cells;
      } else if ($rows) {
        // Mark previous row with headings.
        foreach ($cells as $i => $cell) {
          if ($cell['content']) {
            $rows[last_key($rows)][$i]['type'] = 'th';
          }
        }
      }
    }

    if (!$rows) {
      return $this->applyRules($text);
    }

    $out = array();
    $out[] = hsprintf("<table class=\"remarkup-table\">\n");
    foreach ($rows as $cells) {
      $out[] = hsprintf('<tr>');
      foreach ($cells as $cell) {
        $out[] = phutil_tag(
          $cell['type'],
          array(),
          $this->applyRules($cell['content']));
      }
      $out[] = hsprintf("</tr>\n");
    }
    $out[] = hsprintf("</table>\n");

    return phutil_implode_html('', $out);
  }

}
