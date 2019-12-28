<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Cs_filebank
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  Creative Spirits (c) 2018
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
 
$user       = JFactory::getUser();
$userId     = $user->get('id');

if ( $userId == 0 )
{
	echo "You must login to use this application.";
	return;
}

// check if an item is being viewed or downloaded
$jinput = JFactory::getApplication()->input;
$actid = $jinput->get->get('actid', 0, 'int');
$actitem = urldecode($jinput->get->get('actitem', "", 'raw'));  // still todo: xxxxxxxxxxxxxxx try path???
$action = $jinput->get->get('action', 0, 'cmd');

if ( $actid != 0 && $actitem != "" && ( $action == "view" || $action == "download" ))
{
	doView($actid,$actitem,$action == "download");
	die;
}

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_cs_filebank') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'filebankfileform.xml');
$canEdit    = $user->authorise('core.edit', 'com_cs_filebank') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'filebankfileform.xml');
$canCheckin = $user->authorise('core.manage', 'com_cs_filebank');
$canChange  = $user->authorise('core.edit.state', 'com_cs_filebank');
$canDelete  = $user->authorise('core.delete', 'com_cs_filebank');

$hding        = Cs_filebankHelpersCs_filebank::getComponentHeading();

echo $hding;

// check for the special commandline keyword "showid"
$showid = $jinput->get->get('showid', 0, 'int');
if ( $showid != 0 )
	return showId( $showid );

$filter = $jinput->post->get('filter', array(), 'array');
$search_str = isset($filter["search"]) ? $filter["search"] : "";
if ( $search_str != "" )
	return doSearch( $search_str );
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post"
      name="adminForm" id="adminForm">

	<?php echo JLayoutHelper::render('default_filter', array('view' => $this), dirname(__FILE__)); ?>
	<input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>

<?php if($canDelete) : ?>
<script type="text/javascript">

	jQuery(document).ready(function () {
		jQuery('.delete-button').click(deleteItem);
	});

	function deleteItem() {

		if (!confirm("<?php echo JText::_('COM_CS_FILEBANK_DELETE_MESSAGE'); ?>")) {
			return false;
		}
	}
</script>
<?php endif; ?>
<?php
function showId( $id )
{
	$sql = "select * from #__cs_filebank_files where id=$id";
	$db = JFactory::getDbo();
	$db->setQuery($sql);
	$items = $db->loadObjectList();
	$nitems = count($items);
	if ( $nitems == 1 )
	{
		echo "Showing entry \"$id\"<br /><br />";

		echo Cs_filebankHelpersCs_filebank::getSearchResultFromObject( $items[0]);
	}
	
}
function doSearch( $qs )
{
	$nfound = 0;
//echo "Searching for \"$qs\" ...";
//return $nfound;
	
	$flds = getWordList( array( "id","by_username","idate","isize","ictype","icategory","iname","iaccess","idescription"), "," );
	$sql = "select $flds from #__cs_filebank_files where archived=0 ORDER BY idate DESC";
	$db = JFactory::getDbo();
	$db->setQuery($sql);
	$items = $db->loadObjectList();
	$nitems = count($items);
//	echo "<br />Got $nitems records";

	$matches = array();
	foreach( $items as $row )
	{
		if ( ! doesMatchRecord( $row, $qs ) )
			continue;
	
		$matches[] = $row;
	}
	showSearchResults($matches,$qs);	// show search results in Google style
	
	return count( $matches); 
}
function getWordList( $arr, $sep ) //{{{1
{
	$ret = "";
	$n = count( $arr );
	if ( $n < 1 )
		return $ret;

	for ( $i = 0 ; $i < $n - 1 ; $i++ )
		$ret .= ( $arr[$i] . $sep );
	$ret .= $arr[$i];

	return $ret;
}
function doesMatchRecord( $row, $qs ) // idea: could be in trl/lib (got from memdb)
{
	// to done: kludge from old com_fb - use "showid=id" instead
	/*
	if ( strncmp( $qs, "fld_id_", 7  ) == 0 )
	{
		if ( $row["id"] == substr( $qs, 7 ) )
			return true;
	}
	*/
	foreach( $row as $key => $val )
	{
		// exact match of a field if ( strcasecmp( $val, $qs ) == 0 )
		if ( ! ( stristr( $val, $qs ) === FALSE ) )
		{
			//print "match=" .  $val . "<br>";
			return true;
		}
	}

	return false;
}
function showSearchResults( $rows, $qs )	// show search results in google style
{
	$nrows = count( $rows );

	if ( ! $nrows )
	{
		echo "No matches found for " . "<span style='font-weight: bold;'>$qs</span>";
		//todoclearSession();
		return;
	}

	//todosaveSession( $qs );
	if ( $nrows != 1 )
		$suf = "es";

	echo<<<EOT
		<p>$nrows match$suf found for <span style='font-weight: bold;'>$qs</span></p>
EOT;

	foreach( $rows as $row )
		echo Cs_filebankHelpersCs_filebank::getSearchResult( $row->id, $row->iname, $row->ictype, $row->idescription, $row->icategory, $row->isize, $row->by_username, $row->idate, $row->iaccess );
}
function doView( $actid, $actitem, $bDownload = false )
{
	$sql = "SELECT * FROM #__cs_filebank_files WHERE id=$actid";
	$db = JFactory::getDbo();
	$db->setQuery($sql);
	$items = $db->loadObjectList();
	if ( count($items) != 1 )
	{
		echo "item $actid not found in db";
		return;
	}
	// check actitem matches db record
	$actitem = stripslashes($actitem);

	if ( $actitem != $items[0]->iname )
	{
		
		echo "Item \"$actitem\" does not exist.";
		return;
	}
	// check if the file exists

	$path = Cs_filebankHelpersCs_filebank::getItemFilePath( $items[0]->id );

	if ( empty( $path ) )
	{
		echo "Item \"$actitem\" is offline.";
		return;
	}

	if ( $bDownload )
	{
		$file = $items[0]->iname;
		header("Content-Type: application/x-download");
		header("Content-Disposition: attachment; filename=\"$file\"");
	}
	else
	{
		header ("Content-Type: " . Cs_filebankHelpersCs_filebank::getContentType( "", $items[0]->ictype ) );
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	}

	header ("Content-Length: " . $items[0]->isize );

	readfile($path);
}
?>