<?php

/**
 * Find files on disk matching criteria, like the 'find' system utility. Use of
 * this class is straightforward:
 *
 *    // Find PHP files in /tmp
 *    $files = id(new FileFinder('/tmp'))
 *      ->withType('f')
 *      ->withSuffix('php')
 *      ->find();
 *
 * @task  create    Creating a File Query
 * @task  config    Configuring File Queries
 * @task  exec      Executing the File Query
 * @task  internal  Internal
 * @group filesystem
 */
final class FileFinder {

  private $root;
  private $exclude = array();
  private $paths = array();
  private $suffix = array();
  private $type;
  private $generateChecksums = false;
  private $followSymlinks;

  /**
   * Create a new FileFinder.
   *
   * @param string Root directory to find files beneath.
   * @return this
   * @task create
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * @task config
   */
  public function excludePath($path) {
    $this->exclude[] = $path;
    return $this;
  }

  /**
   * @task config
   */
  public function withSuffix($suffix) {
    $this->suffix[] = '*.'.$suffix;
    return $this;
  }

  /**
   * @task config
   */
  public function withPath($path) {
    $this->paths[] = $path;
    return $this;
  }

  /**
   * @task config
   */
  public function withType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * @task config
   */
  public function withFollowSymlinks($follow) {
    $this->followSymlinks = $follow;
    return $this;
  }

  /**
   * @task config
   */
  public function setGenerateChecksums($generate) {
    $this->generateChecksums = $generate;
    return $this;
  }

  /**
   * @task exec
   */
  public function find() {
    $args = array();
    $command = array();

    $command[] = '(cd %s; ';
    $args[] = $this->root;

    $command[] = 'find';
    if ($this->followSymlinks) {
      $command[] = '-L';
    }
    $command[] = '.';

    if ($this->exclude) {
      $command[] = $this->generateList('path', $this->exclude).' -prune';
      $command[] = '-o';
    }

    if ($this->type) {
      $command[] = '-type %s';
      $args[] = $this->type;
    }

    if ($this->suffix) {
      $command[] = $this->generateList('name', $this->suffix);
    }

    if ($this->paths) {
      $command[] = $this->generateList('wholename', $this->paths);
    }

    $command[] = '-print0 )';

    list($stdout) = call_user_func_array(
      'execx',
      array_merge(
        array(implode(' ', $command)),
        $args));

    $stdout = trim($stdout);
    if (!strlen($stdout)) {
      return array();
    }

    if (!$this->generateChecksums) {
      return explode("\0", $stdout);
    } else {
      $map = array();
      foreach (explode("\0", $stdout) as $line) {
        $file = $this->root.ltrim($line, '.');
        if (is_dir($file)) {
          $map[$file] = null;
        } else {
          $map[$file] = md5_file($file);
        }
      }
      return $map;
    }
  }

  /**
   * @task internal
   */
  private function generateList($flag, array $items) {
    $items = array_map('escapeshellarg', $items);
    foreach ($items as $key => $item) {
      $items[$key] = '-'.$flag.' '.$item;
    }
    $items = implode(' -o ', $items);
    return '\\( '.$items.' \\)';
  }
}

