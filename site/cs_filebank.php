<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Cs_filebank
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  Creative Spirits (c) 2018
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Cs_filebank', JPATH_COMPONENT);
JLoader::register('Cs_filebankController', JPATH_COMPONENT . '/controller.php');


// Execute the task.
$controller = JControllerLegacy::getInstance('Cs_filebank');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
