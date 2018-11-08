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

$hding = Cs_filebankHelpersCs_filebank::getComponentHeading();

echo "$hding";

$jinput = JFactory::getApplication()->input;
$files = $jinput->files->get('iname', array(), 'raw');
if ( count($files)) {
	echo "<pre>files: ";
	var_dump($files);
	echo "</pre>";
}
/*
files: array(5) {
  ["name"]=>
  string(9) "yugen.jpg"
  ["type"]=>
  string(10) "image/jpeg"
  ["tmp_name"]=>
  string(14) "/tmp/phpBLoubT"
  ["error"]=>
  int(0)
  ["size"]=>
  int(87337)
}
 */
$idescription = $jinput->post->get('idescription','');
if ( $idescription != "")
	echo "<br />idescription=$idescription<br />"; 

?>

<form name="form-upload" id="form-upload"
			  action="<?php echo JRoute::_('index.php?option=com_cs_filebank&view=uploads'); ?>"
			  method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
	File to Upload: <input size="70" name="iname" type="file">
	<br />
	File Description: <input size="70" name="idescription" type="text">
	<br />
	<button type="submit" class="validate btn btn-primary">
							<?php echo "Start Upload"; ?>
	</button>
</form>