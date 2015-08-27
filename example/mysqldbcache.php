<?php

/**
 * MySql DB Cache example page
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aw\Common\Cache\MySqlDbCache;

$dbConfig = array(
    ':hostname' => 'localhost',
    ':database' => '',
    ':username' => '',
    ':password' => '',
);

$pdo = new \PDO(strtr('mysql:host=:hostname;dbname=:database', $dbConfig), $dbConfig[':username'], $dbConfig[':password']);

$cache = new MySqlDbCache($pdo, 'mysqldbcache');

# Save
$cache->save('testkey', array('name'=>'jerrysietsma'), 100);
$cache->save('testkey2', array('name'=>'somebody'), 200);

# Fetch
$fetched = $cache->fetch('testkey');
var_dump($fetched);

# Fetch multiple at once
//$fetchedMultiple = $cache->fetchMultiple(array('testkey', 'testkey2'));
//var_dump($fetchedMultiple);

# Contains check
//var_dump($cache->contains('testkey'));

# Delete
//var_dump($cache->delete('testkey'));

# Delete all
//var_dump($cache->flushAll());