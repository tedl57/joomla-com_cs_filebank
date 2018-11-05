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

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_cs_filebank'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Cs_filebank', JPATH_COMPONENT_ADMINISTRATOR);
JLoader::register('Cs_filebankHelper', JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cs_filebank.php');

$controller = JControllerLegacy::getInstance('Cs_filebank');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
