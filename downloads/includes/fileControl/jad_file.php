<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Качаем JAD файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || (functions::format($res_down['name']) != 'jar' && !isset($_GET['more'])) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4)) {
    echo $lng['not_found_file'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

if (isset($_GET['more'])) {
    $more = abs(intval($_GET['more']));
    $req_more = $db->query("SELECT * FROM `download__more` WHERE `id` = '$more' LIMIT 1");
    $res_more = $req_more->fetch();
    if (!$req_more->rowCount() || !is_file($res_down['dir'] . '/' . $res_more['name']) || functions::format($res_more['name']) != 'jar') {
        echo $lng['not_found_file'] . '<a href="' . $url . '">' . _t('Downloads') . '</a>';
        exit;
    }
    $down_file = $res_down['dir'] . '/' . $res_more['name'];
    $jar_file = $res_more['name'];
} else {
    $down_file = $res_down['dir'] . '/' . $res_down['name'];
    $jar_file = $res_down['name'];
}

if (!isset($_SESSION['down_' . $id])) {
    $db->exec("UPDATE `download__files` SET `field`=`field`+1 WHERE `id`=" . $id);
    $_SESSION['down_' . $id] = 1;
}

$size = filesize($down_file);
require(SYSPATH . 'lib/pclzip.lib.php');
$zip = new PclZip($down_file);
$content = $zip->extract(PCLZIP_OPT_BY_NAME, 'META-INF/MANIFEST.MF', PCLZIP_OPT_EXTRACT_AS_STRING);

App::view()->setLayout(false);
$out = $content[0]['content'] . "\n" . 'MIDlet-Jar-Size: ' . $size . "\n" . 'MIDlet-Jar-URL: ' . App::cfg()->sys->homeurl . $res_down['dir'] . '/' . $jar_file;
Functions::downloadFile($out, basename($down_file) . '.jad');