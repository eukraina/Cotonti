<?PHP
/* ====================
[BEGIN_SED]
File=admin.cache.inc.php
Version=0.0.2
Updated=2009-jan-03
Type=Core.admin
Author=Neocrome & Cotonti Team
Description=Internal cache (Cotonti - Website engine http://www.cotonti.com Copyright (c) Cotonti Team 2009 BSD License)
[END_SED]
==================== */

if (!defined('SED_CODE') || !defined('SED_ADMIN')) { die('Wrong URL.'); }

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = sed_auth('admin', 'a');
sed_block($usr['isadmin']);

$adminpath[] = array(sed_url('admin', 'm=other'), $L['Other']);
$adminpath[] = array (sed_url('admin', 'm=cache'), $L['adm_internalcache']);

$t = new XTemplate(sed_skinfile('admin.cache.inc'));

if ($a=='purge')
{
	$admincache = (sed_check_xg() && sed_cache_clearall()) ? $L['adm_purgeall_done'] : $L['Error'];
}
elseif ($a=='delete')
{
	sed_check_xg();
	$sql = sed_sql_query("DELETE FROM $db_cache WHERE c_name='$id'");
	$admincache = ($sql) ? $L['adm_delcacheitem'] : $L['Error'];
}

$sql = sed_sql_query("SELECT * FROM $db_cache WHERE 1 ORDER by c_name ASC");
$cachesize = 0;

while ($row = sed_sql_fetcharray($sql))
{
	$row['c_value'] = sed_cc($row['c_value']);
	$row['size'] = mb_strlen($row['c_value']);
	$cachesize += $row['size'];
	$t -> assign(array(
		"ADMIN_CACHE_ITEM_DEL_URL" => sed_url('admin', 'm=cache&a=delete&id='.$row['c_name'].'&'.sed_xg()),
		"ADMIN_CACHE_ITEM_NAME" => $row['c_name'],
		"ADMIN_CACHE_EXPIRE" => ($row['c_expire']-$sys['now']),
		"ADMIN_CACHE_SIZE" => $row['size'],
		"ADMIN_CACHE_VALUE" => ($a=='showall') ? $row['c_value'] : sed_cutstring($row['c_value'], 80)
		));
	$t -> parse("CACHE.ADMIN_CACHE_ROW");
}

if(!empty($admincache))
{
	$t -> assign(array("ADMIN_CACHE_MESAGE" => $admincache));
	$t -> parse("CACHE.MESAGE");
}

$t -> assign(array(
	"ADMIN_CACHE_URL_REFRESH" => sed_url('admin', 'm=cache'),
	"ADMIN_CACHE_URL_PURGE" => sed_url('admin', 'm=cache&a=purge&'.sed_xg()),
	"ADMIN_CACHE_URL_SHOWALL" => sed_url('admin', 'm=cache&a=showall'),
	"ADMIN_CACHE_CACHESIZE" => $cachesize
	));

$t -> parse("CACHE");
$adminmain = $t -> text("CACHE");
?>
