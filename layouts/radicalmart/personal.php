<?php
/*
 * @package     RadicalMart Shipping Standard Plugin
 * @subpackage  plg_radicalmart_shipping_standard
 * @version     3.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

defined('_JEXEC') or die;

use Joomla\Plugin\RadicalMartShipping\Standard\Extension\Standard;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  \Joomla\CMS\Form\Form $form      Form object.
 * @var  object                $item      Customer object.
 * @var  object                $shipping  Checkout shipping method object.
 * @var  array                 $fieldsets Checkout shipping method object.
 * @var  string                $group     Fields group target.
 *
 */

if (empty($shipping))
{
	return false;
}

$defaultFieldsParams = Standard::$defaultFieldsParams;
?>
<div id="personal_shipping_method_<?php echo $shipping->id; ?>" class="options-form form-horizontal">
	<div class="row">
		<?php if ($shipping->params->get('field_country', $defaultFieldsParams['country']) !== 'hidden'): ?>
			<div class="col-md-12">
				<?php echo $form->renderField('country', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_region', $defaultFieldsParams['region']) !== 'hidden'): ?>
			<div class="col-md-6">
				<?php echo $form->renderField('region', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_city', $defaultFieldsParams['city']) !== 'hidden'): ?>
			<div class="col-md-6">
				<?php echo $form->renderField('city', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_zip', $defaultFieldsParams['zip']) !== 'hidden'): ?>
			<div class="col-md-2">
				<?php echo $form->renderField('zip', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_street', $defaultFieldsParams['street']) !== 'hidden'): ?>
			<div class="col-md-8">
				<?php echo $form->renderField('street', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_house', $defaultFieldsParams['house']) !== 'hidden'): ?>
			<div class="col-md-2">
				<?php echo $form->renderField('house', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_building', $defaultFieldsParams['building']) !== 'hidden'): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('building', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_entrance', $defaultFieldsParams['entrance']) !== 'hidden'): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('entrance', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_floor', $defaultFieldsParams['floor']) !== 'hidden'): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('floor', $group); ?>
			</div>
		<?php endif; ?>
		<?php if ($shipping->params->get('field_apartment', $defaultFieldsParams['apartment']) !== 'hidden'): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('apartment', $group); ?>
			</div>
		<?php endif; ?>
	</div>
</div>

