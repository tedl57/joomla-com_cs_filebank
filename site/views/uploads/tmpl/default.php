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

abstract class UploadResults {
	const Success = 0;
	const Error1 = 1;
	const Error2 = 2;
	const Error3 = 3;
	const Error4 = 4;
	const Error5 = 5;
	const Error6 = 6;
	const Error7 = 7;
	const Error8 = 8;
	const Error9 = 9;
	const Error10 = 10;	
	const Error11 = 11;
}

function onUploadComplete( $result, $str="" )
{
	if ( $result == UploadResults::Success )
		JFactory::getApplication()->enqueueMessage("Upload Successful! $str");
	else
		JFactory::getApplication()->enqueueMessage("Upload Failed: ($result) $str", 'error');
	
	return $result;
}

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

//$app = JFactory::getApplication();
//$data = $app->getUserState('com_cs_payments.payment.data', array());
// it's fine to not have data here
//if (empty($data))
	//{
//echo('<br />no data infoform');
//}
//else
	//var_dump($data);

//echo "POST:<pre>";var_dump($_POST);
//echo "<br />FILES:";var_dump($_FILES);
//echo "</pre>";


$jinput = JFactory::getApplication()->input;
$files = $jinput->files->get('jform', array(), 'raw');

// check if processing an uploaded file or showing the form to upload a file

if ( count($files == 1) && isset($files["iname"]) && is_array($files["iname"])) 
{
	// processing an uploaded file from the upload form

	// check token from previous form to prevent cross site spoofing
	JSession::checkToken() or die( 'Invalid Token' );

	// integrity check the _FILES and _POST data to prevent spoofing - do nothing (just return) if the data isn't as expected
	
	$uploaded_file = $files["iname"];

	/* $_FILES will have a single array of a 5 element array, for example:

	 uploaded_file: array(5) {
	  	["name"]=>
			string(9) "yugen.jpg"
		["type"]=>
			string(10) "image/jpeg"
		["tmp_name"]=>
			string(14) "/tmp/phpImrbRl"
		["error"]=>
				int(0)
		["size"]=>
			int(87337)
	*/

	if ( count( $uploaded_file ) != 5)
		return onUploadComplete( UploadResults::Error1 );
	
	// make sure all 5 expected file elements are present
	
	$flds = array( "name", "type", "tmp_name", "error", "size" );
	
	foreach ( $flds as $fld )
		if ( ! isset( $uploaded_file[$fld]))
			return onUploadComplete( UploadResults::Error2 );
	
	// check the error code

	if ( $uploaded_file["error"] != UPLOAD_ERR_OK )
		return onUploadComplete( UploadResults::Error3 ); // todo: or log the error for future debugging?
	
	// make sure the file has actually been uploaded by the previous form

	if ( ! is_uploaded_file( $uploaded_file["tmp_name"] ) )
		return onUploadComplete( UploadResults::Error4 );
	
//	$msg = "<br />Uploaded " . $uploaded_file["name"] . " to " . $uploaded_file["tmp_name"] . " with size " . $uploaded_file["size"] . " and type " . $uploaded_file["type"] . "<br />";

	/* check the POSTed data is as expected, for example:
	
	 POST:
array(2) {
  ["976592bcee4cc8830826321f5456c1dc"]=> (token checked above)
  string(1) "1"
  ["jform"]=> (user entered on the upload form)
  array(4) {
    ["icategory"]=>
    string(14) "-New Category-"
    ["newcategory"]=>
    string(18) "Enter New Category"
    ["iaccess"]=>
    string(1) "1"
    ["idescription"]=>
    string(1) "a"
  }
}
	 */

	// get the POST'ed data from the upload form

	$data = $jinput->post->get('jform', array(), 'raw');
	$nflds = count( $data );

	// 3 required and 1 optional values are passed in

	if ( $nflds < 3 || $nflds > 4 )
		return onUploadComplete( UploadResults::Error5 );

	// make sure these 3 required values are set

	$flds = array( "icategory", "newcategory", "idescription" );

	foreach ($flds as $fld)
		if ( ! isset( $data[$fld]) )
			return onUploadComplete( UploadResults::Error6 );

	// check if the user checked the (optional) public access checkbox

	$iaccess = 0;
	if ( $nflds == 4 )
	{
		if ( isset($data["iaccess"]) )
			$iaccess = 1;	// public access checkbox was checked
		else
			return onUploadComplete( UploadResults::Error7 );
	}

//	$msg .= "<br />icategory='".$data["icategory"]."'";
//	$msg .= "<br />newcategory='".$data["newcategory"]."'";
//	$msg .= "<br />idescription='".$data["idescription"]."'";
//	$msg .= "<br />iaccess='$iaccess'";

	// normalize the category based on what the user specified on the upload form
	$default_ctgy = "2sort";	// todo: a kludge because TRY AS I DID, i could not figure out how to make the Category list required
	
	// if the user didn't select a category, use the default; 
	
	 if ( $data["icategory"] == "" )
	 	$data["icategory"] = $default_ctgy;
	 else
	 {
	 	// if the user selected -New Category- and didn't enter a new category, use the default
	 	
	 	if ( $data["icategory"] == "-New Category-" )
	 	{ 
	 		if ( $data["newcategory"] == "debugging")	// todo: easter egg
	 			return onUploadComplete( UploadResults::Error8, "debugging");
	 		
	 		if ( $data["newcategory"] == "" || $data["newcategory"] == "Enter New Category" )
	 			$data["icategory"] = $default_ctgy;
	 		else 
	 			$data["icategory"] = $data["newcategory"];
	 	}
	 }
	
	// insert newly uploaded file record into the db
	// the new id# will be used for unique permanent file storage
	$file_obj = new stdClass();
	$file_obj->id = 0;	// will be replaced with new auto_increment id after insert
	$file_obj->iname = $uploaded_file["name"];
	$file_obj->isize = $uploaded_file["size"];
	$file_obj->ictype = $uploaded_file["type"];
	$file_obj->idescription = $data["idescription"];
	$file_obj->icategory = $data["icategory"];
	$file_obj->iaccess = $iaccess;
	$file_obj->by_username = $user->get('username');
	$file_obj->idate = date('Y-m-d H:i:s', time() );
	
	$db = JFactory::getDbo();
	$result = $db->insertObject('#__cs_filebank_files', $file_obj, 'id');
	
	// todo: check result
	
	if ( $file_obj->id == 0 )
		return onUploadComplete( UploadResults::Error9 );

//	$msg .= "<br />record inserted with id# " . $file_obj->id;
	// move file to fb/group/id#
	// if public access, link file to that public folder
	$newfilepath = Cs_filebankHelpersCs_filebank::getItemFilePath( $file_obj->id, false );

//	$msg .= "<br />Moving file to $newfilepath";
	
	if ( ! @move_uploaded_file($uploaded_file["tmp_name"], $newfilepath ) )
		// todo: IMPORTANT: db record exists for file that doesn't - inconsistent state between DB & filesystem!
		return onUploadComplete( UploadResults::Error10 ); // error: "failed to move file to $newfilepath" );
		
//	$search_result = Cs_filebankHelpersCs_filebank::getSearchResultFromObject( $file_obj );

	// if the file is to be publically accessible, create the link

	if ( $iaccess )
	{
		// insert id number before extension to create "unique" filename
		// create symlink for the filename to be externally accessible
		$inameuniq = Cs_filebankHelpersCs_filebank::getLinkedName( $file_obj->id, $file_obj->iname );
	
		if ( ! @symlink($newfilepath, Cs_filebankHelpersCs_filebank::getLinksFolderPath() . "/$inameuniq" ) )
			return onUploadComplete( UploadResults::Error11 );
	}
	
	// output successfully uploaded message
	onUploadComplete( UploadResults::Success );//, "<br /><br />$search_result" );
	
	// show the upload result on the search page 
	JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_cs_filebank&showid='.$file_obj->id, false));
	
	// idea: how to record "failed upload events"?
	
	// still todo: how to handle session data for the upload and search forms upon re-displaying them
	// when possible, allow category to be set the same as the previous upload to make it easier to upload a series of member/vehicle photos
}

$theModel = JModelLegacy::getInstance('uploads', 'Cs_filebankModel');
$form = $theModel->getForm();

$formhtml = Cs_filebankHelpersCs_filebank::renderForm($form,"form-uploads","uploads");
$formendhtml = Cs_filebankHelpersCs_filebank::renderFormEnd();

// get unique list of existing categories
$sql = "SELECT DISTINCT icategory FROM #__cs_filebank_files ORDER BY icategory ASC";
$db = JFactory::getDbo();
$db->setQuery($sql);
$categories = $db->loadObjectList();

// rewrite the form's HTML with a dynamic list of options for the category list
$newoptions = "";
foreach ($categories as $category )
	// example option: <option value="2sort">2sort</option>
	$newoptions .= sprintf( "\t<option value=\"%s\">%s</option>\n", $category->icategory, $category->icategory );

$findstr = "-New Category-</option>";
$formhtml = str_replace( $findstr, "$findstr\n$newoptions", $formhtml );

echo<<<EOT
	$formhtml

	<button type="submit" class="btn btn-primary">
			<span>Upload File</span>
	</button>

	$formendhtml

<script>
jQuery("#form-uploads").validate();
			
jQuery( document ).ready(function() {
	console.log("ready!")
	//if ( jQuery("input[name='jform\[amount\]']:checked").val() != -1 )
		//jQuery("#cg-jform_otheramount").hide();
	jQuery("#cg-jform_newcategory").hide();
});

jQuery('#jform_newcategory').focus(function(event) {
    setTimeout(function() {jQuery('#jform_newcategory').select();}, 0);
});

// select text when clicking on the new category text field
//jQuery("#jform_newcategory").on("click", function () {
//   jQuery(this).select();
//});
			
jQuery("#jform_icategory").on('change', function (e) {
    //var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    console.log(valueSelected);
	if ( valueSelected == '-New Category-' )
	{
			//jQuery("#jform_newcategory").removeAttr("disabled");
			jQuery("#jform_newcategory").val("Enter New Category");
			jQuery("#cg-jform_newcategory").show();
			jQuery("#jform_newcategory").focus();
    	    jQuery("#jform_newcategory").select();
		//	console.log("removed disabled");
	}
	else
	{
			//jQuery("#jform_newcategory").attr("disabled",true);
			jQuery("#jform_newcategory").val("");
			jQuery("#cg-jform_newcategory").hide();
		//	console.log("added disabled");
	}
});

// prevent focus/selection from being removed when selection category changes
//jQuery("#jform_icategory").mouseup(function(e){
//    e.preventDefault();
//});
		
</script>
EOT;
