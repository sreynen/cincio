<?php

define('S2MDEBUG', FALSE);
define('S2MIMPORT', TRUE);

set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/includes/zendframework1/library/');
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/zendframework1/library/Zend/Loader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/spyc/Spyc.php';

Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

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

/**
 * Returns sheet matching name array.
 */
function sheets_to_worksheet($sheets, $names) {

  $key = FALSE;
  $sheet_names = array_keys($sheets);

  foreach ($sheet_names as $sheet_name) {

    if (in_array(strtolower($sheet_name), $names)) {
      $key = $sheet_name;
    }

  }

  if (!$key) {
    return FALSE;
  }

  return $sheets[$key];

}

/**
 * Creates array of content type info from sheets.
 */
function sheets_to_content_types($sheets) {

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('node types', 'content types'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['machine name']) && !empty($result_row['machine name'])) {
      $result[] = $result_row;
    }

  }

  return $result;

}

/**
 * Creates array of field info from sheets.
 */
function sheets_to_fields($sheets) {

  static $type_synonyms = array(
    'longtext' => 'text_with_summary',
    'long text' => 'text_with_summary',
    'term reference' => 'taxonomy_term_reference',
    'term ref' => 'taxonomy_term_reference',
    'termreference' => 'taxonomy_term_reference',
    'entity reference' => 'entityreference',
    'entity ref' => 'entityreference',
    'entityreference' => 'entityreference',
    'link' => 'link_field',
    'select list' => 'list_text',
    'list (text)' => 'list_text',
    'list' => 'list_text',
    'video embed' => 'video_embed_field',
    'integer' => 'number_integer',
    'boolean' => 'list_boolean',
  );

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('fields'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['type']) && !empty($result_row['type'])) {

      $result_row['type'] = strtolower($result_row['type']);

      if (isset($type_synonyms[$result_row['type']])) {
        $result_row['type'] = $type_synonyms[$result_row['type']];
      }

    }

    if (isset($result_row['field settings'])) {

      try {
        $result_row['field settings'] = spyc_load($result_row['field settings']);
      } catch (Exception $e) {
        unset($result_row['field settings']);
      }

    }


    if (isset($result_row['machine name']) && !empty($result_row['machine name'])) {
      $result[] = $result_row;
    }

  }

  return $result;

}

/**
 * Creates array of field instance info from sheets.
 */
function sheets_to_field_instances($sheets) {

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('node types', 'content types'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  $current_type = FALSE;

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['machine name']) && !empty($result_row['machine name'])) {
      $current_type = $result_row['machine name'];
    }

    if (isset($result_row['field settings'])) {

      try {
        $result_row['field settings'] = spyc_load($result_row['field settings']);
      } catch (Exception $e) {
        unset($result_row['field settings']);
      }

    }

    if (isset($result_row['field machine name']) && !empty($result_row['field machine name'])) {

      if (!isset($result[$current_type])) {
        $result[$current_type] = array();
      }

      $result[$current_type][] = $result_row;

    }

  }

  if (S2MDEBUG) {

    echo '<pre>';
    echo '## sheets_to_field_instances ##' . "\n";
    print_r($result);
    echo '</pre>';

  }

  return $result;

}

/**
 * Creates array of image style info from sheets.
 */
function sheets_to_image_styles($sheets) {

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('image styles', 'imagestyles'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['style name']) && !empty($result_row['style name'])) {
      $result[] = $result_row;
    }

  }

  return $result;

}

/**
 * Creates array of menu info from sheets.
 */
function sheets_to_menus($sheets) {

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('menus'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['machine name']) && !empty($result_row['machine name'])) {
      $result[] = $result_row;
    }

  }

  return $result;

}

/**
 * Creates array of vocab info from sheets.
 */
function sheets_to_vocabs($sheets) {

  $result = array();
  $columns = array();
  $worksheet = sheets_to_worksheet($sheets, array('vocabs', 'vocabularies'));

  if (!$worksheet) {
    return FALSE;
  }

  $header = array_shift($worksheet);

  foreach ($header as $index => $name) {
    $columns[strtolower($name)] = $index;
  }

  foreach ($worksheet as $row) {

    $result_row = array();

    foreach ($columns as $name => $index) {
      if (isset($row[$index])) {
        $result_row[$name] = $row[$index];
      }
    }

    if (isset($result_row['machine name']) && !empty($result_row['machine name'])) {
      $result[] = $result_row;
    }

  }

  return $result;

}

/**
 * Creates array of field collection info from sheets.
 */
function sheets_to_field_collections($sheets) {

  return FALSE;

}

/**
 * Creates tar export of content types.
 */
function tar_from_content_types($content_types) {

  foreach ($content_types as $content_type) {

    $type = array(
      'name' => $content_type['name'],
      'type' => $content_type['machine name'],
      'description' => isset($content_type['description']) ? $content_type['description'] : '',
      'create_body' => FALSE,
    );

    print create_tar('sheet2module_export/config/install/node.type.' . $content_type['machine name'] . '.yml', Spyc::YAMLDump($type, false, 0, true));

  }

}

/**
 * Creates tar export of fields.
 */
function tar_from_fields($fields, &$field_name_to_type) {

  if (S2MDEBUG) {
    echo '<pre>';
    echo '## tar_from_fields ##' . "\n";
    print_r($fields);
    echo '</pre>';
  }

  foreach ($fields as $field) {

    if (!isset($field['entity type'])) {
      $field['entity type'] = 'node';
    }

    $field_export = array(
      'id' => $field['entity type'] . '.' . $field['machine name'],
      'field_name' => $field['machine name'],
      'entity_type' => $field['entity type'],
      'type' => $field['type'],
      'cardinality' => $field['# values'],
      'settings' => isset($field['field settings']) && is_array($field['field settings']) ? $field['field settings'] : array(),
    );

    $field_name_to_type[$field['machine name']] = $field['type'];

    print create_tar('sheet2module_export/config/install/field.storage.' . $field['entity type'] . '.' . $field['machine name'] . '.yml', Spyc::YAMLDump($field_export, false, 0, true));

  }

}

/**
 * Creates tar export of field instances.
 */
function tar_from_field_instances($field_instances, $field_name_to_type) {

  foreach ($field_instances as $node_type => $type_field_instances) {

    foreach ($type_field_instances as $field) {

      if (isset($field_name_to_type[$field['field machine name']])) {

        $field_export = array(
          'id' => 'node.' . $node_type . '.' . $field['field machine name'],
          'label' => $field['label'],
          'entity_type' => 'node',
          'bundle' => $node_type,
          'field_type' => $field_name_to_type[$field['field machine name']],
          'field_name' => $field['field machine name'],
          'settings' => isset($field['field settings']) && is_array($field['field settings']) ? $field['field settings'] : array(),
          'dependencies' => array(
            'entity' => array(
              'field.storage.node.' . $field['field machine name'],
              'node.type.' . $node_type,
            ),
          ),
        );

        print create_tar('sheet2module_export/config/install/field.field.node.' . $node_type . '.' . $field['field machine name'] . '.yml', Spyc::YAMLDump($field_export, false, 0, true));

      }

    }

  }

}

/**
 * Creates tar export of image styles.
 */
function tar_from_image_styles($image_styles) {

  foreach ($image_styles as $image_style) {

    $style = array(
      'name' => $image_style['style name'],
    );

    print create_tar('sheet2module_export/config/install/image.style.' . $image_style['style name'] . '.yml', Spyc::YAMLDump($style, false, 0, true));

  }

}

/**
 * Creates tar export of vocabs.
 */
function tar_from_vocabs($vocabs) {

  foreach ($vocabs as $vocab) {

    $taxonomy_vocab = array(
      'name' => $vocab['name'],
      'vid' => $vocab['machine name'],
      'description' => $vocab['description'],
    );

    print create_tar('sheet2module_export/config/install/taxonomy.vocabulary.' . $vocab['machine name'] . '.yml', Spyc::YAMLDump($taxonomy_vocab, false, 0, true));

  }

}

/**
 * Creates tar export of menus.
 */
function tar_from_menus($menus) {

  foreach ($menus as $menu) {

    $menu_export = array(
      'id' => $menu['machine name'],
      'label' => $menu['label'],
    );

    print create_tar('sheet2module_export/config/install/system.menu.' . $menu['machine name'] . '.yml', Spyc::YAMLDump($menu_export, false, 0, true));

  }

}

if (isset($_POST['key']) && !empty($_POST['key'])) {

  $user = $_POST['email'];
  $pass = $_POST['pass'];
  $key = $_POST['key'];

  if (S2MIMPORT && isset($_GET['import'])) {

    $sheets = spyc_load(file_get_contents('./tests/' . $_GET['import']));

  }
  else {

    $sheets = array();

    $service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
    try {
      $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
    }
    catch (Exception $e){
      exit("Authentication failed");
    }
    $service = new Zend_Gdata_Spreadsheets($client);

    $sheet = $service->getSpreadsheetEntry('https://spreadsheets.google.com/feeds/spreadsheets/' . $key);

    $worksheet_feed = $sheet->getWorksheets();

    foreach($worksheet_feed as $worksheet) {

      $sheet_title = $worksheet->getTitle() . '';

      if (!isset($sheets[$sheet_title])) {
        $sheets[$sheet_title] = array();
      }

      $cells = $service->getCellFeed($worksheet);

      foreach ($cells as $cell) {
        $row = $cell->getCell()->getRow() - 2;
        $column = $cell->getCell()->getColumn() - 1;
        $value = $cell->getCell()->getText();

        if ($row >= 0) {

          if (!isset($sheets[$sheet_title][$row])) {
            $sheets[$sheet_title][$row] = array();
          }

          $sheets[$sheet_title][$row][$column] = $value;

        }

      }

    }

  }

  if (isset($_GET['export'])) {

    print spyc_dump($sheets);
    die();

  }

  if (S2MDEBUG) {

/*
    echo '<pre>';
    echo '## $sheets ##' . "\n";
    print_r($sheets);
    echo '</pre>';
*/

  }

  $content_types = sheets_to_content_types($sheets);
  $fields = sheets_to_fields($sheets);
  $field_instances = sheets_to_field_instances($sheets);
  $image_styles = sheets_to_image_styles($sheets);
  $menus = sheets_to_menus($sheets);
  $vocabs = sheets_to_vocabs($sheets);
  $field_collections = sheets_to_field_collections($sheets);

  $filename = 'sheet2module.tar';

  if (!S2MDEBUG) {

    header('Content-type: application/x-tar');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

  }

  if ($_POST['version'] == '7') {

    $info = implode("\n", array(
      'name = Sheet2Module Export',
      "description = 'Configuration created from Sheet2Module.'",
      'version = 1.0',
      'core = 7.x',
      '',
      'dependencies[] = cinc_yaml',
    ));

    print create_tar('sheet2module_export/sheet2module_export.info', $info);
    print create_tar('sheet2module_export/sheet2module_export.module', '<?php' . "\n");

  }
  else {

    $info = implode("\n", array(
      'name: Sheet2Module Export',
      'type: module',
      "description: 'Configuration created from Sheet2Module.'",
      'version: 1.0',
      'core: 8.x'
    ));

    print create_tar('sheet2module_export/sheet2module_export.info.yml', $info);

  }

  if ($content_types) {
    tar_from_content_types($content_types);
  }

  $field_name_to_type = array();

  if ($fields) {
    tar_from_fields($fields, $field_name_to_type);
  }

  if ($field_instances) {
    tar_from_field_instances($field_instances, $field_name_to_type);
  }

  if ($image_styles) {
    tar_from_image_styles($image_styles);
  }

  if ($vocabs) {
    tar_from_vocabs($vocabs);
  }

  if ($menus) {
    tar_from_menus($menus);
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
      <h1>Sheet2Module</h1>
      <p>This tool takes a Google Sheet and auto-generates a Drupal module with the full configuration. The Google Sheet is identified by key in the URL. Copy <a href="https://docs.google.com/spreadsheet/ccc?key=0Ak5zX7FSC8XFdG1TcC1nNmE1cm8tQmJ5SXRyVkNOWWc">the public template</a> to start your own Google Sheet. <strong>Note:</strong> This is still a work in progress and the template will likely change.</p>
      <form class="form-horizontal" role="form" action="/playground/sheet2module/<?php if (isset($_GET['export'])) { print '?export=1'; } ?><?php if (isset($_GET['import'])) { print '?import=' . urlencode($_GET['import']); } ?>" method="post">
        <div class="form-group">
          <label class="col-sm-3 control-label">Google Account Email</label>
          <div class="col-sm-6">
            <input class="form-control" type="text" size="20" name="email" value="<?php print isset($_POST['email']) ? $_POST['email'] : ''; ?>" />
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label">Google Account Pass</label>
          <div class="col-sm-6">
            <input class="form-control" type="password" size="20" name="pass" />
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label">Google Sheet Key</label>
          <div class="col-sm-6">
            <input class="form-control" type="text" size="20" name="key" value="<?php print isset($_POST['key']) ? $_POST['key'] : ''; ?>" />
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label">Drupal Version</label>
          <div class="col-sm-2">
            <input type="radio" name="version" value="8" checked /> Drupal 8
          </div>
          <div class="col-sm-2">
            <input type="radio" name="version" value="7" /> Drupal 7 (CINC)
          </div>
        </div>

        <div class="form-group">
          <div class="col-sm-offset-3 col-sm-6">
            <button type="submit" class="btn btn-default">Download Module</button>
          </div>
        </div>
      </form>
    </div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.inc'); ?>
