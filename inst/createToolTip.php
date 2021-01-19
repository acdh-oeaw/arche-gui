<?php
#!/path/to/php

require_once  'var/www/drupal/git/vendor/autoload.php';

$cfgFile = __DIR__ . '/modules/custom/arche-gui/config.ini';

use acdhOeaw\util\RepoConfig as RC;
RC::init($cfgFile);

// create a new cURL resource
$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, RC::get('guiBaseUrl')."/oeaw_cache_ontology");
curl_setopt($ch, CURLOPT_HEADER, 0);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// grab URL and pass it to the browser
curl_exec($ch);

// close cURL resource, and free up system resources
curl_close($ch);

echo "\n Tooltip imported! \n";


?>
