<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.inc'); ?>
  <body>
    <div class="navbar navbar-default">
      <div class="container">
        <div class="navbar-header">
          <a href="/" class="navbar-brand">CINC</a>
        </div>
          <ul class="nav navbar-nav ">
            <li><a href="https://www.drupal.org/project/cinc">Drupal Module</a></li>
            <li><a href="/playground">Playground</a></li>
            <li class="active"><a href="/links">Links</a></li>
          </ul>
      </div>
    </div>
    <div class="container">
      <p>Some tools that deal with configuration in code outside this site:</p>
      <h4><a href="https://www.contentful.com/developers/documentation/content-management-api/http/#resources-content-types">Contentful content type API</a></h4>
      <p>Contentful's API defines a JSON structure for content types.</p>
      <h4><a href="http://intake.center/">Intake.Center</a></h4>
      <p>Intake.Center exports and imports content and user configuration in a JSON format.</p>
      <h4><a href="https://www.drupal.org/sandbox/churel/2160815">Merlin</a></h4>
      <p>Merlin is a Drupal 8 module that generates PHP configuration (for the Features module), as well as Behat tests, from a configuration spreadsheet.</p>
      <h4><a href="https://github.com/FGM/tooling">Tooling</a></h4>
      <p>Tooling parses Drupal menu YAML files and builds an image of a site's menu tree.</p>
    </div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.inc'); ?>