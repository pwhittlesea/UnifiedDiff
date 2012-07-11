<?php
/**
*
* Diff
* Parses the Unified Diff format
* Supports Git and SVN
*
* Heavily modified from an original concept by the InDefero team
*
* Licensed under The MIT License
* Redistributions of files must retain the above copyright notice.
*
* @copyright     Phillip Whittlesea 2012
* @copyright     Céondo Ltd and contributors 2008
* @link          http://github.com/pwhittlesea/UnifiedDiff
* @link          http://projects.ceondo.com/p/indefero
* @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
*/

class Diff {

    public function parse($diff) {
        $lines = preg_split("/\015\012|\015|\012/", $diff);

        $_file_array = array('hunks' => array(), 'hunks_def' => array());

        $current_file = '';
        $current_hunk = 0;

        // Before line number
        $b_line = 0;

        // After line number
        $a_line = 0;

        $files = array();

        // Used to skip the headers in the git patches
        $indiff = false;

        foreach ($lines as $line) {
            if (preg_match("#^diff --git a/(\S+) b/\S+$#", $line, $matches)) {
                // GIT: file header
                $current_file = $matches[1];
                $files[$current_file] = $_file_array;

                // Reset file counter and maintain diff
                $current_hunk = 0;
                $indiff = true;

                continue;
            } else if (preg_match('#^Index: (\S+)$#', $line, $matches)) {
                // SVN: file header
                $current_file = $matches[1];
                $files[$current_file] = $_file_array;

                // Reset file counter and maintain diff
                $current_hunk = 0;
                $indiff = true;
                continue;
            }

            // If we havent picked up a file diff carry on calmly
            if (!$indiff) {
                continue;
            }

            if (preg_match('#^@@ -([0-9]+),?([0-9]+)? \+([0-9]+),?([0-9]+)? @@ ?(.*)#', $line, $matches)) {
                $files[$current_file]['hunks_def'][$current_hunk] = array(
                    '-' => array($matches[1], $matches[2]),
                    '+' => array($matches[3], $matches[4]),
                    'heading' => $matches[5]
                );

                // Prepare for diffs
                $files[$current_file]['hunks'][] = array();

                // Take down the line numbers to use
                $b_line = (int) $files[$current_file]['hunks_def'][$current_hunk]['-'][0];
                $a_line = (int) $files[$current_file]['hunks_def'][$current_hunk]['+'][0];

                $current_hunk++;

                continue;
            }

            // Padding lines that we dont really care about
            if (preg_match('#^[+-]{3,3}#', $line)) {
                continue;
            }

            // Removed lines
            if (preg_match('#^-(.*)$#', $line, $matches)) {
                $files[$current_file]['hunks'][$current_hunk-1][] = array('-', $b_line++, null, $matches[1]);

                continue;
            }

            // Additional lines
            if (preg_match('#^\+(.*)$#', $line, $matches)) {
                $files[$current_file]['hunks'][$current_hunk-1][] = array('+', null, $a_line++, $matches[1]);

                continue;
            }

            // Context lines
            if (preg_match('#^\s(.*)$#', $line, $matches)) {
                $files[$current_file]['hunks'][$current_hunk-1][] = array(' ', $b_line++, $a_line++, substr($line, 1));

                continue;
            }
        }

        return $files;
    }
}