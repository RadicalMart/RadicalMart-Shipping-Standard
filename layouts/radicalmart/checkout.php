<?php
/*
 * @package     RadicalMart Shipping Standard Plugin
 * @subpackage  plg_radicalmart_shipping_standard
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\Plugin\RadicalMartShipping\Standard\Extension\Standard;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  Form   $form     Form object.
 * @var  object $item     Checkout object.
 * @var  object $shipping Checkout shipping method object.
 *
 */

if (empty($shipping))
{
	return false;
}

$defaultFieldsParams = Standard::$defaultFieldsParams;
?>
<div class="row">
	<?php if ($shipping->params->get('field_country', $defaultFieldsParams['country']) !== 'hidden'): ?>
		<div class="col-md-12"><?php echo $form->renderField('country', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_region', $defaultFieldsParams['region']) !== 'hidden'): ?>
		<div class="col-md-6"><?php echo $form->renderField('region', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_city', $defaultFieldsParams['city']) !== 'hidden'): ?>
		<div class="col-md-6"><?php echo $form->renderField('city', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_zip', $defaultFieldsParams['zip']) !== 'hidden'): ?>
		<div class="col-md-2"><?php echo $form->renderField('zip', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_street', $defaultFieldsParams['street']) !== 'hidden'): ?>
		<div class="col-md-8"><?php echo $form->renderField('street', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_house', $defaultFieldsParams['house']) !== 'hidden'): ?>
		<div class="col-md-2"><?php echo $form->renderField('house', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_building', $defaultFieldsParams['building']) !== 'hidden'): ?>
		<div class="col-md-3"><?php echo $form->renderField('building', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_entrance', $defaultFieldsParams['entrance']) !== 'hidden'): ?>
		<div class="col-md-3"><?php echo $form->renderField('entrance', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_floor', $defaultFieldsParams['floor']) !== 'hidden'): ?>
		<div class="col-md-3"><?php echo $form->renderField('floor', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_apartment', $defaultFieldsParams['apartment']) !== 'hidden'): ?>
		<div class="col-md-3"><?php echo $form->renderField('apartment', 'shipping'); ?></div>
	<?php endif; ?>
	<?php if ($shipping->params->get('field_comment', $defaultFieldsParams['comment']) !== 'hidden'): ?>
		<div class="col-md-12"><?php echo $form->renderField('comment', 'shipping'); ?></div>
	<?php endif; ?>
</div>

