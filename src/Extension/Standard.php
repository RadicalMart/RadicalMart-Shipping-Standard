<?php
/*
 * @package     RadicalMart Shipping Standard Plugin
 * @subpackage  plg_radicalmart_shipping_standard
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\Standard\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper as RadicalMartPriceHelper;
use Joomla\Component\RadicalMartExpress\Administrator\Helper\PriceHelper as RadicalMartExpressPriceHelper;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class Standard extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  1.2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  1.2.0
	 */
	protected $app = null;

	/**
	 * Enable on RadicalMart
	 *
	 * @var  bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public bool $radicalmart = true;

	/**
	 * Enable on RadicalMartExpress
	 *
	 * @var  bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public bool $radicalmart_express = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentNormaliseRequestData'                  => 'onContentNormaliseRequestData',
			'onRadicalMartGetShippingMethods'                => 'onRadicalMartGetShippingMethods',
			'onRadicalMartGetOrderTotal'                     => 'onGetOrderTotal',
			'onRadicalMartGetOrderForm'                      => 'onGetOrderForm',
			'onRadicalMartGetOrderCustomerUpdateData'        => 'onGetOrderCustomerUpdateData',
			'onRadicalMartGetCheckoutCustomerData'           => 'onGetCheckoutCustomerData',
			'onRadicalMartGetCustomerMethodForm'             => 'onGetCustomerMethodForms',
			'onRadicalMartGetPersonalMethodForm'             => 'onGetCustomerMethodForms',
			'onRadicalMartExpressGetShippingMethods'         => 'onRadicalMartExpressGetShippingMethods',
			'onRadicalMartExpressGetOrderTotal'              => 'onGetOrderTotal',
			'onRadicalMartExpressGetOrderForm'               => 'onGetOrderForm',
			'onRadicalMartExpressGetOrderCustomerUpdateData' => 'onGetOrderCustomerUpdateData',
			'onRadicalMartExpressGetCheckoutCustomerData'    => 'onGetCheckoutCustomerData',
			'onRadicalMartExpressGetCustomerMethodForm'      => 'onGetCustomerMethodForms',
			'onRadicalMartExpressGetPersonalMethodForm'      => 'onGetCustomerMethodForms',
		];
	}

	/**
	 * Prepare RadicalMart prices data.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  1.1.0
	 */
	public function onContentNormaliseRequestData(Event $event)
	{
		$context = $event->getArgument('0');
		$objData = $event->getArgument('1');
		if ($context === 'com_radicalmart.shippingmethod')
		{
			foreach ($objData->prices as &$price)
			{
				$price['base'] = RadicalMartPriceHelper::clean($price['base'], $price['currency']);
			}
		}
	}

	/**
	 * Prepare RadicalMart order shipping method data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $formData  Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  \Exception
	 *
	 * @since  1.1.0
	 */
	public function onRadicalMartGetShippingMethods(string $context, object $method, array $formData,
	                                                array  $products, array $currency)
	{
		// Set disabled
		$method->disabled = false;

		// Set price
		if (!empty($formData['shipping']['price']))
		{
			$price = $formData['shipping']['price'];
		}
		else
		{
			$price = (isset($method->prices[$currency['group']])) ? $method->prices[$currency['group']]
				: ['base' => 0];
		}

		$price = $this->prepareRadicalMartPrice($price, $currency['code']);

		// Set order
		$method->order              = new \stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;

		// Set layout
		if ($context === 'com_radicalmart.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.standard.checkout';
		}
	}

	/**
	 * Prepare Radicalmart price values.
	 *
	 * @param   array        $price  Item price array.
	 * @param   string|null  $code   Currency code.
	 *
	 * @throws \Exception
	 *
	 * @return array Formatting price array, False on failure.
	 *
	 * @since  1.1.0
	 */
	protected function prepareRadicalMartPrice(array $price = [], string $code = null): array
	{
		// Set base price
		$price['base']        = RadicalMartPriceHelper::clean($price['base'], $code);
		$price['base_string'] = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['base'], $code);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['base'], $code, 'seo');
		$price['base_number'] = RadicalMartPriceHelper::toString($price['base'], $code, false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['final'], $code);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['final'], $code, 'seo');
		$price['final_number'] = RadicalMartPriceHelper::toString($price['final'], $code, false);

		return $price;
	}

	/**
	 * Prepare RadicalMart Express order shipping method data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $formData  Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartExpressGetShippingMethods(string $context, object $method, array $formData,
	                                                       array  $products, array $currency)
	{
		// Set disabled
		$method->disabled = false;

		// Set price
		if (!empty($formData['shipping']['price']))
		{
			$price = $formData['shipping']['price'];
		}
		else
		{
			$price = (isset($method->price['base'])) ? $method->price : ['base' => 0];
		}

		$price = $this->prepareRadicalMartExpressPrice($price);

		$title = (!empty($method->title) && $method->title !== Text::_('COM_RADICALMART_EXPRESS_SHIPPING'))
			? $method->title : Text::_('PLG_RADICALMART_SHIPPING_STANDARD_EXPRESS_TITLE');

		// Set order
		$method->order              = new \stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;

		// Set layout
		if ($context === 'com_radicalmart.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.standard.express.checkout';
		}
	}

	/**
	 * Prepare Radicalmart price values.
	 *
	 * @param   array  $price  Item price array.
	 *
	 * @throws \Exception
	 *
	 * @return array Formatting price array, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function prepareRadicalMartExpressPrice(array $price = []): array
	{
		// Set base price
		$price['base']        = RadicalMartExpressPriceHelper::clean($price['base']);
		$price['base_string'] = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base']);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base'], 'seo');
		$price['base_number'] = RadicalMartExpressPriceHelper::toString($price['base'], false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final']);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final'], 'seo');
		$price['final_number'] = RadicalMartExpressPriceHelper::toString($price['final'], false);

		return $price;
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express order totals.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   array   $total     Order total data.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onGetOrderTotal(string $context, array &$total, array $formData, object $shipping,
	                                object $payment, array $currency)
	{
		if (!empty($shipping->order->price['base']))
		{
			$total['base'] += $shipping->order->price['base'];
		}

		if (!empty($shipping->order->price['final']))
		{
			$total['final'] += $shipping->order->price['final'];
		}
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express order form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   Form    $form      Order form object.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onGetOrderForm(string $context, Form $form, array $formData, object $shipping,
	                               object $payment)
	{
		// Remove fields
		$fields = ['country', 'city', 'zip', 'street', 'house', 'building', 'entrance', 'floor', 'apartment', 'comment'];
		foreach ($fields as $field)
		{
			if ((int) $shipping->params->get('field_' . $field, 1) === 0)
			{
				$form->removeField($field, 'shipping');
			}
		}

		// Set default price
		if (!empty($shipping->order->price['base']))
		{
			$form->setFieldAttribute('base', 'default', $shipping->order->price['base'], 'shipping.price');
		}
	}

	/**
	 * Get RadicalMart & RadicalMart Express order customer update data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $order     Order data.
	 * @param   object  $customer  Customer data method data.
	 *
	 * @return array|false Update customer data if success, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onGetOrderCustomerUpdateData(string $context, object $order, object $customer)
	{
		$result = false;
		if (!empty($order->formData['shipping']))
		{
			$result = [];
			foreach ($order->formData['shipping'] as $key => $value)
			{
				if ($key === 'price' || $key === 'id')
				{
					continue;
				}
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * Get RadicalMart & RadicalMart Express checkout customer data.
	 *
	 * @param   string  $context       Context selector string.
	 * @param   object  $shipping      Shipping method object.
	 * @param   array   $customerData  Customer data method data.
	 *
	 * @return array|false Customer shipping data for merge.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onGetCheckoutCustomerData(string $context, object $shipping, array $customerData)
	{
		return (!empty($customerData)) ? $customerData : false;
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express customer and personal forms.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   Form    $form      Custer shipping method form object.
	 * @param   mixed   $data      The data expected for the form.
	 * @param   object  $shipping  Shipping method data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onGetCustomerMethodForms(string $context, Form $form, $data, object $shipping)
	{
		$fields = ['country', 'city', 'zip', 'street', 'house', 'building', 'entrance', 'floor', 'apartment', 'comment'];
		foreach ($fields as $field)
		{
			if ((int) $shipping->params->get('field_' . $field, 1) === 0)
			{
				$form->removeField($field);
			}
		}
	}
}