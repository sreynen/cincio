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
          </ul>
      </div>
    </div>
    <div class="container">
      <h1>Playground</h1>
      <p>Some configuration tools:</p>
      <div class="list-group">
        <a href="/playground/tsv2module" class="list-group-item">TSV2Module : Creates Drupal content type configuration modules from TSV input.</a>
      </div>
    </div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.inc'); ?>