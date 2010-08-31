<?php
/**
 * Admin function library.
 *
 * @package Cotonti
 * @version 0.7.0
 * @author Neocrome, Cotonti Team
 * @copyright Copyright (c) 2008-2010 Cotonti Team
 * @license BSD License
 */

defined('SED_CODE') or die('Wrong URL.');

// Requirements
sed_require_api('extrafields');
sed_require_api('forms');
sed_require_lang('admin', 'core');
sed_require_rc('admin');

/* ======== Defaulting the admin variables ========= */

unset($adminmain, $adminhelp, $admin_icon, $plugin_body, $plugin_title, $plugin_help);
$adminpath = array();

/**
 * Builds administration breadcrumbs (path)
 *
 * @param array $adminpath Path links
 * @return string
 */
function sed_build_adminsection($adminpath)
{
	global $cfg, $L;

	$result = array();
	$result[] = '<a href="'.sed_url('admin').'">'.$L['Adminpanel'].'</a>';
	foreach ($adminpath as $i => $k)
	{
		$result[] = '<a href="'.$k[0].'">'.$k[1].'</a>';
	}
	$result = implode(' '.$cfg['separator'].' ', $result);

	return $result;
}

/**
 * Returns $url as an HTML link if $cond is TRUE or just plain $text otherwise
 * @param string $url Link URL
 * @param string $text Link text
 * @param bool $cond Condition
 * @return string
 */
function sed_linkif($url, $text, $cond)
{
	if ($cond)
	{
		$res = '<a href="'.$url.'">'.$text.'</a>';
	}
	else
	{
		$res = $text;
	}

	return $res;
}

/**
 * Returns a list of possible charsets
 *
 * @return array
 */
function sed_loadcharsets()
{
	// FIXME this function is obviously obsolete
	$result = array();
	$result[] = array('ISO-10646-UTF-1', 'ISO-10646-UTF-1 / Universal Transfer Format');
	$result[] = array('UTF-8', 'UTF-8 / Standard Unicode');
	$result[] = array('ISO-8859-1', 'ISO-8859-1 / Western Europe');
	$result[] = array('ISO-8859-2', 'ISO-8859-2 / Middle Europe');
	$result[] = array('ISO-8859-3', 'ISO-8859-3 / Maltese');
	$result[] = array('ISO-8859-4', 'ISO-8859-4 / Baltic');
	$result[] = array('ISO-8859-5', 'ISO-8859-5 / Cyrillic');
	$result[] = array('ISO-8859-6', 'ISO-8859-6 / Arabic');
	$result[] = array('ISO-8859-7', 'ISO-8859-7 / Greek');
	$result[] = array('ISO-8859-8', 'ISO-8859-8 / Hebrew');
	$result[] = array('ISO-8859-9', 'ISO-8859-9 / Turkish');
	$result[] = array('ISO-2022-KR', 'ISO-2022-KR / Korean');
	$result[] = array('ISO-2022-JP', 'ISO-2022-JP / Japanese');
	$result[] = array('windows-1250', 'windows-1250 / Central European');
	$result[] = array('windows-1251', 'windows-1251 / Russian');
	$result[] = array('windows-1252', 'windows-1252 / Western Europe');
	$result[] = array('windows-1254', 'windows-1254 / Turkish');
	$result[] = array('EUC-JP', 'EUC-JP / Japanese');
	$result[] = array('GB2312', 'GB2312 / Chinese simplified');
	$result[] = array('BIG5', 'BIG5 / Chinese traditional');
	$result[] = array('tis-620', 'Tis-620 / Thai');
	return $result;
}

/**
 * Returns a list of possible DOCTYPEs
 *
 * @return array
 */
function sed_loaddoctypes()
{
	$result = array();
	$result[] = array(0, 'HTML 4.01');
	$result[] = array(1, 'HTML 4.01 Transitional');
	$result[] = array(2, 'HTML 4.01 Frameset');
	$result[] = array(3, 'XHTML 1.0 Strict');
	$result[] = array(4, 'XHTML 1.0 Transitional');
	$result[] = array(5, 'XHTML 1.0 Frameset');
	$result[] = array(6, 'XHTML 1.1');
	$result[] = array(7, 'XHTML 2');
	return $result;
}

/**
 * Removes a category
 *
 * @param int $id Category ID
 * @param string $c Category code
 */
function sed_structure_delcat($id, $c)
{
	global $db_structure, $db_auth, $cfg, $cot_cache;

	$sql = sed_sql_query("DELETE FROM $db_structure WHERE structure_id='$id'");
	sed_auth_remove_item('page', $c);
	$cot_cache && $cot_cache->db->remove('sed_cat', 'system');
}

/**
 * Adds a new category
 *
 * @param string $code Category code
 * @param string $path Path string, dot-separated
 * @param string $title Category title
 * @param string $desc Description
 * @param string $icon Icon URL
 * @param bool $group Is group of categories
 * @param string $order Order by field
 * @param string $way Sorting direction
 * @param array $extra_array Extra fields: names => values
 * @return bool Operation status
 */
function sed_structure_newcat($code, $path, $title, $desc, $icon, $group, $order, $way, $extra_array = array())
{
	global $db_structure, $db_auth, $sed_groups, $usr, $cfg, $cot_cache;

	$res = FALSE;

	if (!empty($title) && !empty($code) && !empty($path) && $code != 'all')
	{
		$sql = sed_sql_query("SELECT structure_code FROM $db_structure WHERE structure_code='".sed_sql_prep($code)."' LIMIT 1");
		if (sed_sql_numrows($sql) == 0)
		{
			$colname = '';
			$colvalue = '';
			if (is_array($extra_array))
			{
				while (list($i, $x) = each($extra_array))
				{
					$colname .= ", structure_".$i;
					$colvalue .= ", '".sed_sql_prep($x)."'";
				}
			}
			$sql = sed_sql_query("INSERT INTO $db_structure (structure_code, structure_path, structure_title, structure_desc, structure_icon, structure_group, structure_order".$colname.") VALUES ('".sed_sql_prep($code)."', '".sed_sql_prep($path)."', '".sed_sql_prep($title)."', '".sed_sql_prep($desc)."', '".sed_sql_prep($icon)."', ".(int)$group.", '".sed_sql_prep($order.'.'.$way)."'".$colvalue.")");

			$auth_permit = array(
				COT_GROUP_DEFAULT => 7,
				COT_GROUP_GUESTS => 5,
				COT_GROUP_MEMBERS => 7
			);

			$auth_lock = array(
				COT_GROUP_DEFAULT => 0,
				COT_GROUP_GUESTS => 250,
				COT_GROUP_MEMBERS => 128
			);

			sed_auth_add_item('page', sed_sql_prep($code), $auth_permit, $auth_lock);
			$res = true;

			$cot_cache && $cot_cache->db->remove('sed_cat', 'system');
		}
	}
	return $res;
}

/**
 * Recalculates category counters
 *
 * @param int $id Category ID
 * @return bool
 */
function sed_structure_resync($id)
{
	global $db_structure, $db_pages;

	$sql = sed_sql_query("SELECT structure_code FROM $db_structure WHERE structure_id='".$id."' ");
	$row = sed_sql_fetcharray($sql);
	$sql = sed_sql_query("SELECT COUNT(*) FROM $db_pages
		WHERE page_cat='".$row['structure_code']."' AND (page_state = 0 OR page_state=2)");
	$num = (int) sed_sql_result($sql, 0, 0);
	return (bool) sed_sql_query("UPDATE $db_structure SET structure_pagecount=$num WHERE structure_id='$id'");
}

/**
 * Recalculates counters
 *
 * @param int $id Category ID
 * @return bool
 */
function sed_structure_resyncall()
{
	global $db_structure;

	$res = TRUE;
	$sql = sed_sql_query("SELECT structure_id FROM $db_structure");
	while ($row = sed_sql_fetchassoc($sql))
	{
		$res &= sed_structure_resync($row['structure_id']);
	}
	sed_sql_freeresult($sql);
	return $res;
}

/**
 * Removes a trash item
 *
 * @param int $id Item ID
 * @return int Number of rows affected
 */
function sed_trash_delete($id)
{
	global $db_trash;

	$sql = sed_sql_query("DELETE FROM $db_trash WHERE tr_id='$id'");
	return sed_sql_affectedrows();
}

/**
 * Fetches a trash item
 *
 * @param int $id
 * @return array
 */
function sed_trash_get($id)
{
	global $db_trash;

	$sql = sed_sql_query("SELECT * FROM $db_trash WHERE tr_id='$id' LIMIT 1");
	if ($res = sed_sql_fetchassoc($sql))
	{
		$res['tr_datas'] = unserialize($res['tr_datas']);
		return $res;
	}
	else
	{
		return FALSE;
	}
}

/**
 * Inserts an item into trash
 *
 * @param array $dat Trash record(s)
 * @param string $db Table name
 * @return bool
 */
function sed_trash_insert($dat, $db)
{
	foreach ($dat as $k => $v)
	{
		$columns[] = $k;
		$datas[] = "'".sed_sql_prep($v)."'";
	}
	$sql = sed_sql_query("INSERT INTO $db (".implode(', ', $columns).") VALUES (".implode(', ', $datas).")");
	return TRUE;
}

/**
 * Restores a trash item
 *
 * @param int $id Trash item ID
 * @return bool Operation success or failure
 */
function sed_trash_restore($id)
{
	global $db_forum_topics, $db_forum_posts, $db_trash;

	$columns = array();
	$datas = array();

	$res = sed_trash_get($id);

	switch($res['tr_type'])
	{
		case 'comment':
			global $db_com;
			sed_trash_insert($res['tr_datas'], $db_com);
			sed_log("Comment #".$res['tr_itemid']." restored from the trash can.", 'adm');
			return (TRUE);
			break;

		case 'forumpost':
			global $db_forum_posts;
			$sql = sed_sql_query("SELECT ft_id FROM $db_forum_topics WHERE ft_id='".$res['tr_datas']['fp_topicid']."'");

			if ($row = sed_sql_fetcharray($sql))
			{
				sed_trash_insert($res['tr_datas'], $db_forum_posts);
				sed_log("Post #".$res['tr_itemid']." restored from the trash can.", 'adm');
				sed_forum_resynctopic($res['tr_datas']['fp_topicid']);
				sed_forum_sectionsetlast($res['tr_datas']['fp_sectionid']);
				sed_forum_resync($res['tr_datas']['fp_sectionid']);
				return TRUE;
			}
			else
			{
				$sql1 = sed_sql_query("SELECT tr_id FROM $db_trash WHERE tr_type='forumtopic' AND tr_itemid='q".$res['tr_datas']['fp_topicid']."'");
				if ($row1 = sed_sql_fetcharray($sql1))
				{
					sed_trash_restore($row1['tr_id']);
					sed_trash_delete($row1['tr_id']);
				}
			}
			break;

		case 'forumtopic':
			global $db_forum_topics;
			sed_trash_insert($res['tr_datas'], $db_forum_topics);
			sed_log("Topic #".$res['tr_datas']['ft_id']." restored from the trash can.", 'adm');

			$sql = sed_sql_query("SELECT tr_id FROM $db_trash WHERE tr_type='forumpost' AND tr_itemid LIKE '%-".$res['tr_itemid']."'");

			while ($row = sed_sql_fetcharray($sql))
			{
				$res2 = sed_trash_get($row['tr_id']);
				sed_trash_insert($res2['tr_datas'], $db_forum_posts);
				sed_trash_delete($row['tr_id']);
				sed_log("Post #".$res2['tr_datas']['fp_id']." restored from the trash can (belongs to topic #".$res2['tr_datas']['fp_topicid'].").", 'adm');
			}

			sed_forum_resynctopic($res['tr_itemid']);
			sed_forum_sectionsetlast($res['tr_datas']['ft_sectionid']);
			sed_forum_resync($res['tr_datas']['ft_sectionid']);
			return TRUE;
			break;

		case 'page':
			global $db_pages, $db_structure;
			sed_trash_insert($res['tr_datas'], $db_pages);
			sed_log("Page #".$res['tr_itemid']." restored from the trash can.", 'adm');
			$sql = sed_sql_query("SELECT page_cat FROM $db_pages WHERE page_id='".$res['tr_itemid']."'");
			$row = sed_sql_fetcharray($sql);
			$sql = sed_sql_query("SELECT structure_id FROM $db_structure WHERE structure_code='".$row['page_cat']."'");
			if (sed_sql_numrows($sql) == 0)
			{
				sed_structure_newcat('restored', 999, 'RESTORED', '', '', 0);
				$sql = sed_sql_query("UPDATE $db_pages SET page_cat='restored' WHERE page_id='".$res['tr_itemid']."'");
			}
			return TRUE;
			break;

		case 'pm':
			global $db_pm;
			sed_trash_insert($res['tr_datas'], $db_pm);
			sed_log("Private message #".$res['tr_itemid']." restored from the trash can.", 'adm');
			return TRUE;
			break;

		case 'user':
			global $db_users;
			sed_trash_insert($res['tr_datas'], $db_users);
			sed_log("User #".$res['tr_itemid']." restored from the trash can.", 'adm');
			return TRUE;
			break;

		default:
			return FALSE;
			break;
	}
}
// =========== Extra fields ================================
/**
 * Extra fields - Return default base html-construction for various types of fields (without value= and name=)
 *
 * @access private
 * @param string $type Type of field (input, textarea etc)
 * @return string
 *
 */
function sed_default_html_construction($type)
{
	global $R;
	$html = '';
	switch($type)
	{
		case 'input':
			$html = $R['input_text'];
			break;

		case 'textarea':
			$html = $R['input_textarea'];
			break;

		case 'select':
			$html = $R['input_select'];
			break;

		case 'checkbox':
			$html = $R['input_checkbox'];
			break;

		case 'radio':
			$html = $R['input_radio'];
			break;
	}
	return $html;
}

/**
 * Add extra field for pages
 *
 * @param string $location Table for adding extrafield
 * @param string $name Field name (unique)
 * @param string $type Field type (input, textarea etc)
 * @param string $html HTML Resource string
 * @param string $variants Variants of values (for radiobuttons, selectors etc)
 * @param string $default Default value
 * @param bool $required Required field
 * @param string $parse Parsing Type (HTML, BBCodes)
 * @param string $description Description of field (optional, for admin)
 * @param bool $noalter Do not ALTER the table, just register the extra field
 * @return bool
 *
 */
function sed_extrafield_add($location, $name, $type, $html, $variants="", $default="", $required=0, $parse='HTML', $description="", $noalter = false)
{
	global $db_extra_fields;
	$fieldsres = sed_sql_query("SELECT field_name FROM $db_extra_fields WHERE field_location='$location'");
	while($row = sed_sql_fetchassoc($fieldsres))
	{
		$extrafieldsnames[] = $row['field_name'];
	}
	if(count($extrafieldsnames)>0) if (in_array($name,$extrafieldsnames)) return 0; // No adding - fields already exist

	// Check table sed_$sql_table - if field with same name exists - exit.
	if (sed_sql_numrows(sed_sql_query("SHOW COLUMNS FROM $location LIKE '%\_$name'")) > 0 && !$noalter)
	{
		return false;
	}
	$fieldsres = sed_sql_query("SHOW COLUMNS FROM $location");
	while ($fieldrow = sed_sql_fetchassoc($fieldsres))
	{
		$column = $fieldrow['Field'];
		// get column prefix in this table
		$column_prefix = substr($column, 0, strpos($column, "_"));
		
		preg_match("#.*?_$name$#",$column,$match);
		if($match[1]!="" && !$noalter) return false; // No adding - fields already exist
		$i++;
	}

	$extf['location'] = $location;
	$extf['name'] = $name;
	$extf['type'] = $type;
	$extf['html'] = $html;
	$extf['variants'] = is_null($variants) ? '' : $variants;
	$extf['default'] = is_null($default) ? '' : $default;
	$extf['required'] = ($required > 0) ? 1 : 0;
	$extf['parse'] = is_null($parse) ? 'HTML' : $parse;
	$extf['description'] = is_null($description) ? '' : $description;
	
	$step1 = sed_sql_insert($db_extra_fields, $extf, 'field_') == 1;
	if ($noalter)
	{
		return $step1;
	}
	switch($type)
	{
		case 'input': $sqltype = "VARCHAR(255)";
			break;
		case 'textarea': $sqltype = "TEXT";
			break;
		case 'select': $sqltype = "VARCHAR(255)";
			break;
		case 'checkbox': $sqltype = "BOOL";
			break;
		case 'radio': $sqltype = "VARCHAR(255)";
			break;
	}
	$sql = "ALTER TABLE $location ADD ".$column_prefix."_$name $sqltype ";
	$step2 = sed_sql_query($sql);
	return $step1 && $step2;
}

/**
 * Update extra field for pages
 *
 * @param string $location Table contains extrafield
 * @param string $oldname Exist name of field
 * @param string $name Field name (unique)
 * @param string $html HTML Resource string
 * @param string $variants Variants of values (for radiobuttons, selectors etc)
 * @param string $default Default value
 * @param bool $required Required field
 * @param string $parse Parsing Type (HTML, BBCodes)
 * @param string $html HTML Resource string
 * @param string $variants Variants of values (for radiobuttons, selectors etc)
 * @param string $description Description of field (optional, for admin)
 * @return bool
 *
 */
function sed_extrafield_update($location, $oldname, $name, $type, $html, $variants="", $default="", $required=0, $parse='HTML', $description="")
{
	global $db_extra_fields;
	$fieldsres = sed_sql_query("SELECT COUNT(*) FROM $db_extra_fields
			WHERE field_name = '$oldname' AND field_location='$location'");
	if (sed_sql_numrows($fieldsres) <= 0
		|| $name != $oldname
		&& sed_sql_numrows(sed_sql_query("SHOW COLUMNS FROM $location LIKE '%\_$name'")) > 0)
	{
		// Attempt to edit non-extra field or override an existing field
		return false;
	}
	$field = sed_sql_fetchassoc($fieldsres);
	$fieldsres = sed_sql_query("SHOW COLUMNS FROM $location");
	$fieldrow = sed_sql_fetchassoc($fieldsres);
	$column = $fieldrow['Field'];
	$column_prefix = substr($column, 0, strpos($column, "_"));
	$alter = false;
	if ($name != $field['field_name'])
	{
		$extf['name'] = $name;
		$alter = true;
	}
	if ($type != $field['field_type'])
	{
		$extf['type'] = $type;
		$alter = true;
	}

	$extf['html'] = $html;
	$extf['parse'] = is_null($parse) ? 'HTML' : $parse;
	$extf['variants'] = is_null($variants) ? '' : $variants;
	$extf['default'] = is_null($default) ? '' : $default;
	$extf['required'] = ($required > 0) ? 1 : 0;
	$extf['description'] = is_null($description) ? '' : $description;

	$step1 = sed_sql_update($db_extra_fields, "field_name = '$oldname' AND field_location='$location'", $extf, 'field_') == 1;

	if (!$alter) return $step1;

	switch ($type)
	{
		case 'input': $sqltype = "VARCHAR(255)";
			break;
		case 'textarea': $sqltype = "TEXT";
			break;
		case 'select': $sqltype = "VARCHAR(255)";
			break;
		case 'checkbox': $sqltype = "BOOL";
			break;
		case 'radio': $sqltype = "VARCHAR(255)";
			break;
	}
	$sql = "ALTER TABLE $location CHANGE ".$column_prefix."_$oldname ".$column_prefix."_$name $sqltype ";
	$step2 = sed_sql_query($sql);

	return $step1 && $step2;
}

/**
 * Delete extra field
 *
 * @param string $location Table contains extrafield
 * @param string $name Name of extra field
 * @return bool
 *
 */
function sed_extrafield_remove($location, $name)
{
	global $db_extra_fields;
	if ((int) sed_sql_result(sed_sql_query("SELECT COUNT(*) FROM $db_extra_fields
		WHERE field_name = '$name' AND field_location='$location'"), 0, 0) <= 0)
	{
		// Attempt to remove non-extra field
		return false;
	}
	$fieldsres = sed_sql_query("SHOW COLUMNS FROM $location");
	$fieldrow = sed_sql_fetchassoc($fieldsres);
	$column = $fieldrow['Field'];
	$column_prefix = substr($column, 0, strpos($column, "_"));
	$step1 = sed_sql_delete($db_extra_fields, "field_name = '$name' AND field_location='$location'") == 1;
	$sql = "ALTER TABLE $location DROP ".$column_prefix."_".$name;
	$step2 = sed_sql_query($sql);
	return $step1 && $step2;
}

/**
 * Returns forum category dropdown code
 *
 * @param int $check Selected category
 * @param string $name Dropdown name
 * @return string
 */
function sed_selectbox_forumcat($check, $name)
{
	global $usr, $sed_forums_str, $L;

	$result =  "<select name=\"$name\" size=\"1\">";
	if (is_array($sed_forums_str))
		foreach($sed_forums_str as $i => $x)
		{
			$selected = ($i==$check) ? "selected=\"selected\"" : '';
			$result .= "<option value=\"".$i."\" $selected> ".$x['tpath']."</option>";
		}
	$result .= "</select>";
	return $result;
}

/**
 * Returns group selection dropdown code
 *
 * @param string $check Seleced value
 * @param string $name Dropdown name
 * @param array $skip Hidden groups
 * @return string
 */
function sed_selectbox_groups($check, $name, $skip=array(0))
{
	global $sed_groups;

	$res = "<select name=\"$name\" size=\"1\">";

	foreach($sed_groups as $k => $i)
	{
		$selected = ($k == $check) ? "selected=\"selected\"" : '';
		$res .= (in_array($k, $skip)) ? '' : "<option value=\"$k\" $selected>".$sed_groups[$k]['title']."</option>";
	}
	$res .= "</select>";

	return $res;
}

/**
 * Returns substring position in file
 *
 * @param string $file File path
 * @param string $str Needle
 * @param int $maxsize Search limit
 * @return int
 */
function sed_stringinfile($file, $str, $maxsize=32768)
{
	if ($fp = @fopen($file, 'r'))
	{
		$data = fread($fp, $maxsize);
		$pos = mb_strpos($data, $str);
		$result = !($pos === FALSE);
	}
	else
	{
		$result = FALSE;
	}
	@fclose($fp);
	return $result;
}
?>