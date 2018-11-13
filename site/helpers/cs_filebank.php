<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Cs_filebank
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  Creative Spirits (c) 2018
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

JLoader::register('Cs_filebankHelper', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_cs_filebank' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cs_filebank.php');

/**
 * Class Cs_filebankFrontendHelper
 *
 * @since  1.6
 */
class Cs_filebankHelpersCs_filebank
{
	/**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_SITE . '/components/com_cs_filebank/models/' . strtolower($name) . '.php'))
		{
			require_once JPATH_SITE . '/components/com_cs_filebank/models/' . strtolower($name) . '.php';
			$model = JModelLegacy::getInstance($name, 'Cs_filebankModel');
		}

		return $model;
	}

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

    /**
     * Gets the edit permission for an user
     *
     * @param   mixed  $item  The item
     *
     * @return  bool
     */
    public static function canUserEdit($item)
    {
        $permission = false;
        $user       = JFactory::getUser();

        if ($user->authorise('core.edit', 'com_cs_filebank'))
        {
            $permission = true;
        }
        else
        {
            if (isset($item->created_by))
            {
                if ($user->authorise('core.edit.own', 'com_cs_filebank') && $item->created_by == $user->id)
                {
                    $permission = true;
                }
            }
            else
            {
                $permission = true;
            }
        }

        return $permission;
    }
    
    /**
     * Gets the HTML to render the inital heading for the component
     *
     * @return  string
     */
    public static function getComponentHeading()
    {
   		$hding = "<h3>File Bank: <a href='/index.php?option=com_cs_filebank'>Search</a> or <a href='/index.php?option=com_cs_filebank&view=uploads'>Upload</a></h3>";
   		return $hding;
    }
	/* cs_payments helpers to build forms */


	public static function renderFormEnd()
	{
		$ret = "</form>";
		$ret .= "</div>";	// well
		
		return $ret;
	}
	public static function renderForm( $form, $formid, $task )
	{
		$action = "/index.php?option=com_cs_filebank&view=$task";
		
		// style is to highlight error messages
		$ret = "<style type='text/css'>label.error { color: red; }</style>";
		
		$ret .= "<div class='well'>";
		
		$ret .= "<form id='$formid' action='$action' method='post' class='form-horizontal' enctype='multipart/form-data'>";
    	
		// include token to protect from spoofing - will be checked in controller upon submission
		$ret .= JHtml::_('form.token');
		
    	// fieldsets break up a longer form nicely and more clearly specify user workflow
    	$fieldSets = $form->getFieldsets();
    	
    	// fieldset labels in the xml file are fieldset legends in html
    	foreach ($fieldSets as $name => $fieldSet)
    	{
    		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_CS_PAYMENTS'.$name.'_FIELDSET_LABEL';
    		$ret .= "<fieldset>\n<legend>$label</legend>\n";
    	
    		// each field is wrapped within a control-group with label and input
    		foreach ($form->getFieldset($name) as $field)
    		{
    			if ( $field->type == 'echovalue')	// special field type for simple templating
    			{
    				$ret .= "<div class='echovalue'>%" . $field->value . "%</div>\n";
    			}
    			else 
    			{
    				if ( $field->type == 'hidden' )
    				{
    					$ret .= "<input type='hidden' name='" . $field->name . "' value='" . $field->value . "' />\n";
    				}
    				else
    				{
    					$ret .= "<div class='control-group' id='cg-" . $field->id . "'>\n";
    						$ret .= "<div class='control-label'>" . $field->label .	"</div>\n";
    						$ret .= "<div class='controls'>" . $field->input . "</div>\n";
    					$ret .= "</div>\n";
    				}
    			}
    		}
    		$ret .= "</fieldset>\n\n";
    	}
    	
    	return $ret;
	}
	private static function getStoreFolderName()
	{
		return "filebank";	// todo: should be component param
	}
	private static function getStoreFolderLinksName()
	{
		return "fb";		// todo: should be component param
	}
	public static function getLinksFolderPath()
	{
		return $_SERVER["DOCUMENT_ROOT"] . "/" . self::getStoreFolderLinksName();
	}
	public static function getStoreFolderPath()
	{
		return $_SERVER["DOCUMENT_ROOT"] . "/" . self::getStoreFolderName();
	}
	public static function getItemFilePath( $id, $bCheckExisting = true )
	{
		/* 100 files are stored in each folder - 100 folders = 10k files!
		 * ids 0-99 are stored in 0, ids 100-199 are stored in 100, etc.
		 */
		$folder = intval( $id / 100 );
		$folderpath = self::getStoreFolderPath() . "/" . $folder;
		if ( ! is_dir( $folderpath ) )
		{
			if ( ! @mkdir( $folderpath ) )
				die( "ERROR: cannot make directory \"$folderpath\"" );
		}
	
		$filepath = $folderpath . "/" . $id;
	
		if ( ! $bCheckExisting )
			return $filepath;
	
		return ( @stat( $filepath ) === false ) ? "" : $filepath;
	}

	public static function filesize_format ($ibytes,$bbrief=false)	// human readable human friendly format
	{
		$bytes=(float)$ibytes;
		$gbdecimals = 2;
		$mbdecimals = 1;
		if ( $bbrief )
		{
			$gbdecimals = 1;
			$mbdecimals = 0;
		}
	
		if ($bytes<1024)
			$numero=number_format($bytes, 0, '.', '.')." B";
		else if ($bytes<1048576)
			$numero=number_format($bytes/1024, 1, '.', '.')." KB";
		else if ($bytes<1073741824)
		{
			// bug: 1070423994 comes out as 1.020.8 MB iso 1.02GB
			if ( $bytes/1048576 > 1000 )
				$numero=number_format($bytes/1073741824, $gbdecimals, '.', '.')." GB";
			else
				$numero=number_format($bytes/1048576, $mbdecimals, '.', '.')." MB";
		}
		else
			$numero=number_format($bytes/1073741824, $gbdecimals, '.', '.')." GB";
	
		return strval($numero);
	}
	public static function getSpaces($n=4)
	{
		$ret = "";
		for ( $i = 0 ; $i < $n ; $i++ )
			$ret .= "&nbsp;";
		return $ret;
	}
	public static function getSearchResultFromObject( $obj )
	{
		return self::getSearchResult( $obj->id, $obj->iname, $obj->ictype, $obj->itype, $obj->idescription, $obj->icategory, $obj->isize, $obj->by_username, $obj->idate, $obj->iaccess );
	}
	public static function getSearchResult( $id, $iname, $ictype, $itype, $idescription, $icategory, $isize, $by_username, $idate, $iaccess )
	{
		// show file size in abbreviated human readable form
		
		$fsz = self::filesize_format( $isize );
		
		$icategory = empty($icategory) ? "none" : $icategory;
	
		$ret = "<div><p><span style='font-weight: bold;'>Filename: </span> $iname";
		$ret .= "<br /><span style='font-weight: bold;'>Description: </span> $idescription";
		$ret .= "<br /><span style='font-weight: bold;'>Category:</span> $icategory";
		$ret .= self::getSpaces();
		$ret .= "<span style='font-weight: bold;'>Size:</span> $fsz";
		$ret .= self::getSpaces();
		$ret .= "<span style='font-weight: bold;'>From:</span> $by_username on $idate" . "<br />";
		$ret .= self::getItemActions( $id, $itype, $ictype, $iname, $iaccess == "1" );
		$ret .= "</div></p><br />";
		
		return $ret;
	}
	private static function getItemActions( $id, $typ, $ctyp, $actitem, $public_link = false )
	{
		$typ = strtolower( $typ );
	
		// todo: urlencode the filename???

		$baseurl = "/index.php?option=com_cs_filebank&actid=$id&actitem=" . urlencode($actitem);
		
		// if file is viewable in the browser, make it a link
		if ( self::isFileViewable( $typ, $ctyp ) )
		{
			//$actitem = addslashes($actitem);
			$url = "$baseurl&action=view";
			$vu = "<a title='Click to View in a New Window' href='$url' onclick=\"javascript: window.open('$url','','toolbar=yes,location=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=780,height=550&'); return false\">View</a>" . self::getSpaces();
		}
		
		// create download link (always possible)
		$url = "$baseurl&action=download";
		$dl = "<a title='Click to download a copy of this file' href='$url' onclick=\"javascript: window.open('$url','','toolbar=yes,location=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=780,height=550&'); return false\">Download</a>" . self::getSpaces();

		$email = ""; // todo: idea to add back the ability to email a file - "<a title='Email a copy of this file' href='$mmuri&action=email&actid=$id'>Email</a>" . getSpaces();
	
		$link = "<a title='Entry for this file' href='/fb?$id'>Entry</a>" . self::getSpaces();
		
		if ( $public_link )
		{
			$inameuniq = self::getLinkedName( $id, $actitem );
			$llink = "<a title='Public Link for this file' href='/" . self::getStoreFolderLinksName() . "/$inameuniq'>Public Link</a>" . self::getSpaces();
		}
	
		return "$dl$vu$link$llink$email";
	}
	private static function isFileViewable(  $typ, $ctyp )
	{
		$mimetype = self::getContentType( $typ, $ctyp );
		return ! empty( $mimetype );
	}
	public static function getContentType( $typ, $ctyp )	// mime types
	{
		//list of extensions in the first 500 isea files:
		//NULL doc dot gif htm html jpg ksh mp3 pdf ppt qif txt xls zip
		//
		// todo: support application/zip
	
		if ( ! empty( $ctyp ) )
		{
			return $ctyp;	// todo: this will mess up isfileviewable
		}
	
		switch( $typ )
		{
			case "jpg":
				$typ = "jpeg";
				// fall thru
			case "jpeg":
			case "gif":
			case "png":
				return "image/$typ";
			case "mp3":
				return "audio/$typ";
			case "htm":
				$typ = "html";
				// fall thru
			case "html":
				return "text/$typ";
			case "pdf":
				return "application/$typ";
			case "css":
			case "txt":
			case "php":
				return "text/plain";
			default:
				return "";	// todo ?
		}
	}
	public static function getLinkedName( $id, $nm )
	{
		$path_parts = pathinfo($nm);
		return $path_parts["filename"] . "_$id." . $path_parts["extension"];
	}
}