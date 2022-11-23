<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_standard
 * @version     1.0.0
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;

class plgRadicalMart_ShippingStandard extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  1.0.0
	 */
	protected $app = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Prepare prices data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $objData  Input data.
	 * @param   Form    $form     Joomla Form object.
	 *
	 * @throws  Exception
	 *
	 * @since  1.0.0
	 */
	public function onContentNormaliseRequestData($context, $objData, $form)
	{
		if ($context === 'com_radicalmart.shippingmethod')
		{
			JLoader::register('PriceHelper', JPATH_ADMINISTRATOR . '/components/com_radicalmart/helpers/price.php');

			foreach ($objData->prices as &$price)
			{
				$price['base'] = PriceHelper::rounding($price['base'], $price['currency']);
			}
		}
	}

	/**
	 * Prepare order shipping method data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $formData  Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  Exception
	 *
	 * @since  1.0.0
	 */
	public function onRadicalMartGetShippingMethods($context, $method, $formData, $products, $currency)
	{
		// Set disabled
		$method->disabled = false;

		// Set price
		if (!empty($formData['shipping']['price'])) $price = $formData['shipping']['price'];
		else $price = (isset($method->prices[$currency['group']])) ? $method->prices[$currency['group']]
			: array('base' => 0);
		$price = $this->preparePrice($price, $currency['code']);

		// Set order
		$method->order              = new stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;
		if ($context === 'com_radicalmart.checkout') $method->layout = 'plugins.radicalmart_shipping.standard.checkout';
	}

	/**
	 * Prepare order form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   Form    $form      Order form object.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 *
	 * @since 1.0.0
	 */
	public function onRadicalMartGetOrderForm($context, $form, $formData, $shipping, $payment)
	{
		// Remove fields
		foreach (array('country', 'city', 'zip', 'street', 'house', 'building', 'entrance',
			         'floor', 'apartment', 'comment') as $field)
		{
			if (!$shipping->params->get('field_' . $field, 1)) $form->removeField($field, 'shipping');
		}

		// Set default price
		if (!empty($shipping->order->price['base']))
		{
			$form->setFieldAttribute('base', 'default', $shipping->order->price['base'], 'shipping.price');
		}
	}

	/**
	 * Prepare order totals.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   array   $total     Order total data.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function onRadicalMartGetOrderTotal($context, &$total, $formData, $shipping, $payment, $currency)
	{
		if (!empty($shipping->order->price['base'])) $total['base'] += $shipping->order->price['base'];
		if (!empty($shipping->order->price['final'])) $total['final'] += $shipping->order->price['final'];
	}

	/**
	 * Prepare price values.
	 *
	 * @param   array   $price  Item price array.
	 * @param   string  $code   Currency code.
	 *
	 * @throws Exception
	 *
	 * @return array Formatting price array, False on failure.
	 *
	 * @since  1.0.0
	 */
	protected function preparePrice($price = array(), $code = null)
	{
		// Set base price
		$price['base']        = PriceHelper::clean($price['base'], $code);
		$price['base_string'] = (empty($price['base'])) ?
			Text::_('COM_RADICALMART_PRICE_FREE')
			: PriceHelper::toString($price['base'], $code);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: PriceHelper::toString($price['base'], $code, 'seo');
		$price['base_number'] = PriceHelper::toString($price['base'], $code, false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: PriceHelper::toString($price['final'], $code);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: PriceHelper::toString($price['final'], $code, 'seo');
		$price['final_number'] = PriceHelper::toString($price['final'], $code, false);

		return $price;
	}
}