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

namespace Joomla\Plugin\RadicalMartShipping\Standard\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper as RadicalMartPriceHelper;
use Joomla\Component\RadicalMartExpress\Administrator\Helper\PriceHelper as RadicalMartExpressPriceHelper;
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
	 * @since  2.0.0
	 */
	public bool $radicalmart = true;

	/**
	 * Enable on RadicalMartExpress
	 *
	 * @var  bool
	 *
	 * @since  2.0.0
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
			'onRadicalMartNormaliseRequestData'       => 'onRadicalMartNormaliseRequestData',
			'onRadicalMartGetOrderShippingMethods'    => 'onRadicalMartGetOrderShippingMethods',
			'onRadicalMartGetOrderForm'               => 'onGetOrderForm',
			'onRadicalMartGetOrderTotal'              => 'onGetOrderTotal',
			'onRadicalMartGetOrderCustomerUpdateData' => 'onGetOrderCustomerUpdateData',
			'onRadicalMartGetCheckoutCustomerData'    => 'onGetCheckoutCustomerData',
			'onRadicalMartGetCustomerMethodForm'      => 'onGetCustomerMethodForm',
			'onRadicalMartGetPersonalShippingMethods' => 'onGetPersonalShippingMethods',
			'onRadicalMartGetPersonalMethodForm'      => 'onGetCustomerMethodForm',

			'onRadicalMartExpressGetOrderShippingMethods'    => 'onRadicalMartExpressGetOrderShippingMethods',
			'onRadicalMartExpressGetOrderForm'               => 'onGetOrderForm',
			'onRadicalMartExpressGetOrderTotal'              => 'onGetOrderTotal',
			'onRadicalMartExpressGetOrderCustomerUpdateData' => 'onGetOrderCustomerUpdateData',
			'onRadicalMartExpressGetCheckoutCustomerData'    => 'onGetCheckoutCustomerData',
			'onRadicalMartExpressGetCustomerMethodForm'      => 'onGetCustomerMethodForm',
			'onRadicalMartExpressGetPersonalShippingMethods' => 'onGetPersonalShippingMethods',
			'onRadicalMartExpressGetPersonalMethodForm'      => 'onGetCustomerMethodForm',
		];
	}

	/**
	 * Prepare RadicalMart method prices data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $objData  Form data object.
	 * @param   Form    $form     The form object.
	 *
	 * @throws \Exception
	 *
	 * @since 2.0.0
	 */
	public function onRadicalMartNormaliseRequestData(string $context, object $objData, Form $form)
	{
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
	public function onRadicalMartGetOrderShippingMethods(string $context, object $method, array $formData,
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

		// Set base price
		$code                 = $currency['code'];
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
			$method->layout = 'plugins.radicalmart_shipping.standard.radicalmart.checkout';
		}

		// Set notification data
		if (!empty($formData['shipping']) && !empty($formData['shipping']['id'])
			&& (int) $formData['shipping']['id'] === $method->id)
		{
			$method->notification = $this->prepareMethodNotification($formData['shipping'], 'COM_RADICALMART');
		}
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
	 * @since  2.0.0
	 */
	public function onRadicalMartExpressGetOrderShippingMethods(string $context, object $method, array $formData,
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

		// Set base price
		$price['base']        = RadicalMartExpressPriceHelper::clean($price['base']);
		$price['base_string'] = (empty($price['base'])) ? Text::_('COM_RADICALMART_EXPRESS_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base']);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('COM_RADICALMART_EXPRESS_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base'], 'seo');
		$price['base_number'] = RadicalMartExpressPriceHelper::toString($price['base'], false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('COM_RADICALMART_EXPRESS_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final']);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('COM_RADICALMART_EXPRESS_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final'], 'seo');
		$price['final_number'] = RadicalMartExpressPriceHelper::toString($price['final'], false);

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
		if ($context === 'com_radicalmart_express.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.standard.radicalmart_express.checkout';
		}

		// Set notification data
		if (!empty($formData['shipping']))
		{
			$method->notification = $this->prepareMethodNotification($formData['shipping'], 'COM_RADICALMART_EXPRESS');
		}
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express order form.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   Form               $form      Order form object.
	 * @param   array              $formData  Form data array.
	 * @param   array|null|false   $products  Shipping method data.
	 * @param   object|null|false  $shipping  Shipping method data.
	 * @param   object|null|false  $payment   Payment method data.
	 * @param   array              $currency  Order currency data.
	 *
	 * @since 2.0.0
	 */
	public function onGetOrderForm(string $context, Form $form, array $formData, $products, $shipping, $payment, array $currency)
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

		// Remove empty fields in site_order form
		if (strpos($form->getName(), 'order_site') !== false)
		{
			foreach ($form->getFieldset('shipping') as $field)
			{
				if (empty($formData['shipping'][$field->fieldname]))
				{
					$form->removeField($field->fieldname, 'shipping');
				}
			}
		}

		// Set default price
		if (!empty($shipping->order->price['base']))
		{
			$form->setFieldAttribute('base', 'default', $shipping->order->price['base'], 'shipping.price');
		}
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express order totals.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   array              $total     Order total data.
	 * @param   array              $formData  Form data array.
	 * @param   array|null|false   $products  Shipping method data.
	 * @param   object|null|false  $shipping  Shipping method data.
	 * @param   object|null|false  $payment   Payment method data.
	 * @param   array              $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since 2.0.0
	 */
	public function onGetOrderTotal(string $context, array &$total, array $formData, $products, $shipping, $payment,
	                                array  $currency)
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
	 * Get RadicalMart & RadicalMart Express order customer update data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $order     Order data.
	 * @param   object  $customer  Customer data method data.
	 *
	 * @return array|false Update customer data if success, False if not.
	 *
	 * @since 2.0.0
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
	 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	public function onGetCustomerMethodForm(string $context, Form $form, $data, object $shipping)
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

	/**
	 * Prepare RadicalMart personal shipping method data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $method   Method data.
	 *
	 * @throws  \Exception
	 *
	 * @since  2.0.0
	 */
	public function onGetPersonalShippingMethods(string $context, object $method)
	{
		$method->layout = (strpos($context, 'com_radicalmart_express.') !== false)
			? 'plugins.radicalmart_shipping.standard.radicalmart_express.personal'
			: 'plugins.radicalmart_shipping.standard.radicalmart.personal';
	}

	/**
	 * Method to prepare shipping notification information.
	 *
	 * @param   array   $data      Shipping form data.
	 * @param   string  $constant  Component contestant.
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	protected function prepareMethodNotification(array $data, string $constant): array
	{

		$address = [];
		if (!empty($data['zip']))
		{
			$address[] = $data['zip'];
		}
		if (!empty($data['country']))
		{
			$address[] = $data['country'];
		}

		if (!empty($data['city']))
		{
			$address[] = $data['city'];
		}

		$mb = function_exists('mb_strtolower');
		foreach (['street', 'house', 'building', 'entrance', 'floor', 'apartment'] as $key)
		{
			if (!empty($data[$key]))
			{
				$title     = Text::_($constant . '_' . $key);
				$title     = ($mb) ? mb_strtolower($title) : strtolower($title);
				$address[] = $title . ' ' . $data[$key];
			}
		}

		$result = [];
		if (!empty($address))
		{
			$result[$constant . '_SHIPPING_ADDRESS'] = implode(' ', $address);
		}

		if (!empty($data['date']))
		{
			$result['PLG_RADICALMART_SHIPPING_STANDARD_SHIPPING_DATE'] = (new Date($data['date']))->format(Text::_('DATE_FORMAT_LC4'));
		}
		if (!empty($data['note']))
		{
			$result['PLG_RADICALMART_SHIPPING_STANDARD_SHIPPING_NOTE'] = $data['note'];
		}

		return $result;
	}
}