<?php

/**
 * Creates a tar file.
 * Directly copied from features_tar_create().
 */
function create_tar($name, $contents) {
  /* http://www.mkssoftware.com/docs/man4/tar.4.asp */
  /* http://www.phpclasses.org/browse/file/21200.html */
  $tar = '';
  $bigheader = $header = '';
  if (strlen($name) > 100) {
    $bigheader = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
        '././@LongLink', '0000000', '0000000', '0000000',
        sprintf("%011o", strlen($name)), '00000000000',
        '        ', 'L', '', 'ustar ', '0',
        '', '', '', '', '', '');

    $bigheader .= str_pad($name, floor((strlen($name) + 512 - 1) / 512) * 512, "\0");

    $checksum = 0;
    for ($i = 0; $i < 512; $i++) {
      $checksum += ord(substr($bigheader, $i, 1));
    }
    $bigheader = substr_replace($bigheader, sprintf("%06o", $checksum)."\0 ", 148, 8);
  }
 $header = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", // book the memorie area
    substr($name,0,100),  //  0     100     File name
    '100644 ',            // File permissions
    '   765 ',            // UID,
    '   765 ',            // GID,
    sprintf("%11s ", decoct(strlen($contents))), // Filesize,
    sprintf("%11s", decoct(time())),       // Creation time
    '        ',        // 148     8         Check sum for header block
    '',                // 156     1         Link indicator / ustar Type flag
    '',                // 157     100     Name of linked file
    'ustar ',          // 257     6         USTAR indicator "ustar"
    ' ',               // 263     2         USTAR version "00"
    '',                // 265     32         Owner user name
    '',                // 297     32         Owner group name
    '',                // 329     8         Device major number
    '',                // 337     8         Device minor number
    '',                // 345     155     Filename prefix
    '');               // 500     12         ??

  $checksum = 0;
  for ($i = 0; $i < 512; $i++) {
    $checksum += ord(substr($header, $i, 1));
  }
  $header = substr_replace($header, sprintf("%06o", $checksum)."\0 ", 148, 8);
  $tar = $bigheader.$header;

  $buffer = str_split($contents, 512);
  foreach ($buffer as $item) {
    $tar .= pack("a512", $item);
  }
  return $tar;
}

if ($_POST['input']) {

  $lines = explode("\n", $_POST['input']);

  $filename = 'content_types.tar';

  header('Content-type: application/x-tar');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  if ($_POST['version'] == 'd7') {

    $info = implode("\n", array(
      'name = Content Types',
      "description = 'Content Types created from TSV2Module.'",
      'version = 1.0',
      'core = 7.x',
      '',
      'dependencies[] = cinc_yaml',
    ));

    print create_tar('content_types/content_types.info', $info);
    print create_tar('content_types/content_types.module', '<?php' . "\n");

  }
  else {

    $info = implode("\n", array(
      'name: Content Types',
      'type: module',
      "description: 'Content Types created from TSV2Module.'",
      'version: 1.0',
      'core: 8.x'
    ));

    print create_tar('content_types/content_types.info.yml', $info);

  }

  foreach ($lines as $line) {

    list($machine, $display, $description) = explode("\t", trim($line));

    $type = implode("\n", array(
      "name: '" . $display . "'",
      'type: ' . $machine,
      "description: '" . $description . "'"
    ));

    print create_tar('content_types/config/install/node.type.' . $machine . '.yml', $type);

  }

  print pack('a1024', '');
  exit;

}

?>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.inc'); ?>
  <body>
    <div class="navbar navbar-default">
      <div class="container">
        <div class="navbar-header">
          <a href="/" class="navbar-brand">CINC</a>
        </div>
          <ul class="nav navbar-nav ">
            <li><a href="https://www.drupal.org/project/cinc">Drupal Module</a></li>
            <li class="active"><a href="/playground">Playground</a></li>
            <li><a href="/links">Links</a></li>
          </ul>
      </div>
    </div>
    <div class="container">
      <h1>TSV2Module</h1>
      <p>This tool takes TSV text (e.g. text copied and pasted from <a href="https://docs.google.com/spreadsheets/d/1buZBkiR_vyhnBha9WGRapIKpPxZMQ4glP3p0Yjlagbk/edit?usp=sharing">a Google Sheet</a>) and auto-generates a Drupal module with the full content type configuration. The input has 3 columns: machine name, display name, and description.</p>
      <form action="/playground/tsv2module/" method="post">
        <p>
          <input type="radio" name="version" value="d8" checked> Drupal 8 <input type="radio" name="version" value="d7" selected> Drupal 7 (CINC)
        </p>
        <textarea rows="10" cols="100" name="input">example	Example	Examples show how something works.
event	Event	Events are a thing that happens at a time.
project	Project	Projects are things we make.</textarea>
        <p>
          <input type="submit" value="Download Module" class="btn btn-lg btn-primary" />
        </p>
      </form>
    </div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.inc'); ?>