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
use Joomla\Registry\Registry;

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
	 * Default shipping fields params.
	 *
	 * @var array|string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static array $defaultFieldsParams = [
		'country'   => 'required',
		'region'    => 'not_required',
		'city'      => 'required',
		'zip'       => 'required',
		'street'    => 'required',
		'house'     => 'required',
		'building'  => 'not_required',
		'entrance'  => 'not_required',
		'floor'     => 'not_required',
		'apartment' => 'not_required',
		'comment'   => 'not_required'
	];

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
			'onRadicalMartGetOrderShipping'           => 'onRadicalMartGetOrderShipping',
			'onRadicalMartGetOrderShippingMethods'    => 'onGetOrderShippingMethods',
			'onRadicalMartGetOrderForm'               => 'onGetOrderForm',
			'onRadicalMartLoadOrderMethodFormData'    => 'onLoadOrderMethodFormData',
			'onRadicalMartPrepareOrderMethodSaveData' => 'onPrepareOrderMethodSaveData',
			'onRadicalMartGetOrderTotal'              => 'onGetOrderTotal',
			'onRadicalMartGetOrderCustomerUpdateData' => 'onGetOrderCustomerUpdateData',
			'onRadicalMartGetCheckoutCustomerData'    => 'onGetCheckoutCustomerData',
			'onRadicalMartGetCustomerMethodForm'      => 'onGetCustomerMethodForm',
			'onRadicalMartGetPersonalShippingMethods' => 'onGetPersonalShippingMethods',
			'onRadicalMartGetPersonalMethodForm'      => 'onGetCustomerMethodForm',

			'onRadicalMartExpressGetOrderShipping'           => 'onRadicalMartExpressGetOrderShipping',
			'onRadicalMartExpressLoadOrderMethodFormData'    => 'onLoadOrderMethodFormData',
			'onRadicalMartExpressPrepareOrderMethodSaveData' => 'onPrepareOrderMethodSaveData',
			'onRadicalMartExpressGetOrderForm'               => 'onGetOrderForm',
			'onRadicalMartExpressGetOrderShippingMethods'    => 'onGetOrderShippingMethods',
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
	 * Prepare RadicalMart shipping  data.
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
	public function onRadicalMartGetOrderShipping(string $context, object $method, array $formData,
	                                              array  $products, array $currency)
	{
		// Prepare data
		$data = (!empty($formData['shipping'])) ? $formData['shipping'] : [];
		foreach ((new Registry($method->params->get('fields_default', [])))->toArray() as $item)
		{
			if (!empty($item['value']) && empty($data[$item['field']]))
			{
				$data[$item['field']] = $item['value'];
			}
		}

		// Set calculate_price
		$calculate_price = false;
		if ($context === 'com_radicalmart.checkout')
		{
			$calculate_price = true;
		}
		elseif ($context === 'com_radicalmart.order' && isset($data['recalculate_price']))
		{
			$calculate_price = ((int) $data['recalculate_price'] === 1);
		}

		// Set price
		$methodPrice = (isset($method->prices[$currency['group']])) ? $method->prices[$currency['group']] : [];
		if ($calculate_price || empty($data['price']) || !isset($data['price']['base']))
		{
			$price = $methodPrice;
		}
		else
		{
			$price = $data['price'];
		}
		if (!isset($price['base']))
		{
			$price['base'] = 0;
		}

		// Set price values
		$code                 = $currency['code'];
		$price['base']        = RadicalMartPriceHelper::clean($price['base'], $code);
		$price['base_string'] = (empty($price['base'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['base'], $code);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['base'], $code, 'seo');
		$price['base_number'] = RadicalMartPriceHelper::toString($price['base'], $code, false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['final'], $code);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartPriceHelper::toString($price['final'], $code, 'seo');
		$price['final_number'] = RadicalMartPriceHelper::toString($price['final'], $code, false);

		// Set order data
		$method->order                 = new \stdClass();
		$method->order->id             = $method->id;
		$method->order->title          = $method->title;
		$method->order->code           = $method->code;
		$method->order->description    = $method->description;
		$method->order->address_string = $this->addressToString($data);
		$method->order->price          = $price;

		// Set layout
		if ($context === 'com_radicalmart.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.standard.radicalmart.checkout';
		}

		// Set notification
		$method->notification = $this->prepareMethodNotification($data);
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
	public function onRadicalMartExpressGetOrderShipping(string $context, object $method, array $formData,
	                                                     array  $products, array $currency)
	{
		// Prepare data
		$data = (!empty($formData['shipping'])) ? $formData['shipping'] : [];
		foreach ((new Registry($method->params->get('fields_default', [])))->toArray() as $item)
		{
			if (!empty($item['value']) && empty($data[$item['field']]))
			{
				$data[$item['field']] = $item['value'];
			}
		}

		// Set calculate_price
		$calculate_price = false;
		if ($context === 'com_radicalmart_express.checkout')
		{
			$calculate_price = true;
		}
		elseif ($context === 'com_radicalmart_express.order' && isset($data['recalculate_price']))
		{
			$calculate_price = ((int) $data['recalculate_price'] === 1);
		}

		// Set price
		$methodPrice = (isset($method->price)) ? $method->price : [];
		if ($calculate_price || empty($data['price']) || !isset($data['price']['base']))
		{
			$price = $methodPrice;
		}
		else
		{
			$price = $data['price'];
		}
		if (!isset($price['base']))
		{
			$price['base'] = 0;
		}

		// Set price values
		$price['base']        = RadicalMartExpressPriceHelper::clean($price['base']);
		$price['base_string'] = (empty($price['base'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base']);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['base'], 'seo');
		$price['base_number'] = RadicalMartExpressPriceHelper::toString($price['base'], false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final']);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_STANDARD_PRICE_FREE')
			: RadicalMartExpressPriceHelper::toString($price['final'], 'seo');
		$price['final_number'] = RadicalMartExpressPriceHelper::toString($price['final'], false);

		// Set order data
		$method->order                 = new \stdClass();
		$method->order->id             = $method->id;
		$method->order->title          = ((!empty($method->title) && $method->title !== Text::_('COM_RADICALMART_EXPRESS_SHIPPING')))
			? $method->title : Text::_('PLG_RADICALMART_SHIPPING_STANDARD_EXPRESS_TITLE');
		$method->order->code           = $method->code;
		$method->order->description    = $method->description;
		$method->order->address_string = $this->addressToString($data);
		$method->order->price          = $price;

		// Set layout
		if ($context === 'com_radicalmart_express.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.standard.radicalmart_express.checkout';
		}

		// Set notification
		$method->notification = $this->prepareMethodNotification($data);
	}

	/**
	 * Prepare loaded RadicalMart & RadicalMart Express form data.
	 *
	 * @param   string   $context   Context selector string.
	 * @param   array   &$data      Method saved  data.
	 * @param   object   $method    Order shipping method object.
	 * @param   array    $formData  Order form data.
	 * @param   array    $products  Order products data.
	 * @param   array    $currency  Order currency data.
	 * @param   bool     $isNew     Is new order.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onLoadOrderMethodFormData(string $context, array &$data, object $method, array $formData,
	                                          array  $products, array $currency, bool $isNew)
	{
		// Set all order data to form data
		foreach ((new Registry($method->order))->toArray() as $key => $value)
		{
			$data[$key] = $value;
		}

		// Cleanup actions
		$data['recalculate_price'] = 0;
	}

	/**
	 * Prepare and clean RadicalMart & RadicalMart Express order save data.
	 *
	 * @param   string   $context   Context selector string.
	 * @param   array   &$data      Method saved  data.
	 * @param   object   $method    Order shipping method object.
	 * @param   array    $formData  Order form data.
	 * @param   array    $products  Order products data.
	 * @param   array    $currency  Order currency data.
	 * @param   bool     $isNew     Is new order.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onPrepareOrderMethodSaveData(string $context, array &$data, object $method, array $formData,
	                                             array  $products, array $currency, bool $isNew)
	{
		// Cleanup data
		unset($data['address_string']);
		unset($data['recalculate_price']);
		unset($data['data']['address_string']);
		unset($data['data']['recalculate_price']);
	}


	/**
	 * Prepare RadicalMart & RadicalMart Express order shipping methods.
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
	public function onGetOrderShippingMethods(string $context, object $method, array $formData,
	                                          array  $products, array $currency)
	{
		// Set disabled
		$method->disabled = false;
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

		$formName = $form->getName();
		if (!in_array($formName, ['com_radicalmart.checkout', 'com_radicalmart.order', 'com_radicalmart.order_site',
			'com_radicalmart_express.checkout', 'com_radicalmart_express.order', 'com_radicalmart_express.order_site']))
		{
			return;
		}

		// Remove fields
		$defaults = [];
		foreach ((new Registry($shipping->params->get('fields_default', [])))->toArray() as $item)
		{
			if (!empty($item['value']))
			{
				$defaults[$item['field']] = $item['value'];
			}
		}

		foreach (self::$defaultFieldsParams as $key => $default)
		{
			$display    = $shipping->params->get('field_' . $key, $default);
			$hasDefault = false;
			if (!empty($defaults[$key]))
			{
				$form->setFieldAttribute($key, 'default', $defaults[$key], 'shipping');
				$hasDefault = true;
			}

			if ($formName === 'com_radicalmart.checkout' || $formName === 'com_radicalmart_express.checkout')
			{
				if ($display === 'hidden')
				{
					if ($hasDefault)
					{
						$form->setFieldAttribute($key, 'type', 'hidden', 'shipping');
					}
					else
					{
						$form->removeField($key, 'shipping');
					}

				}
				elseif ($display === 'required')
				{
					$form->setFieldAttribute($key, 'required', 'true', 'shipping');
				}
				else
				{
					$form->setFieldAttribute($key, 'required', 'false', 'shipping');
				}
			}
			elseif ($formName === 'com_radicalmart.order' || $formName === 'com_radicalmart_express.order')
			{
				if ($display === 'hidden')
				{
					if ($hasDefault)
					{
						$form->setFieldAttribute($key, 'readonly', 'true', 'shipping');
					}
					else
					{
						$form->removeField($key, 'shipping');
					}
				}
			}
		}

		if ($formName === 'com_radicalmart.order_site' || $formName === 'com_radicalmart_express.order_site')
		{

			foreach (['address_string', 'comment', 'date', 'note'] as $key)
			{
				if ((empty($formData['shipping']) || empty($formData['shipping'][$key]) && empty($shipping->order->$key)))
				{
					$form->removeField($key, 'shipping');
				}
			}

			if (empty($formData['shipping']['price']['base']))
			{
				$form->removeGroup('shipping.price');
			}
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
			foreach (self::$defaultFieldsParams as $key => $value)
			{
				if ($key !== 'comment'
					&& $order->shipping->params->get('field_' . $key, $value) !== 'hidden'
					&& !empty($order->formData['shipping'][$key]))
				{
					$result[$key] = $order->formData['shipping'][$key];
				}
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
		if (empty($customerData))
		{
			return false;
		}

		$result = [];
		foreach (self::$defaultFieldsParams as $key => $value)
		{
			if ($key !== 'comment'
				&& $shipping->params->get('field_' . $key, $value) !== 'hidden'
				&& !empty($customerData[$key]))
			{
				$result[$key] = $customerData[$key];
			}
		}

		return (!empty($result)) ? $result : false;
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
		foreach (self::$defaultFieldsParams as $key => $default)
		{
			if ($shipping->params->get('field_' . $key, $default) === 'hidden')
			{
				$form->removeField($key);
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
	 * Method to convert address data to string.
	 *
	 * @param   array  $data  Address data
	 *
	 * @return string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addressToString(array $data = []): string
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
		if (!empty($data['region']))
		{
			$address[] = $data['region'];
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
				$title     = Text::_('PLG_RADICALMART_SHIPPING_STANDARD_FIELD_' . $key);
				$title     = ($mb) ? mb_strtolower($title) : strtolower($title);
				$address[] = $title . ' ' . $data[$key];
			}
		}

		return (!empty($address)) ? implode(', ', $address) : '';
	}

	/**
	 * Method to prepare shipping notification information.
	 *
	 * @param   array  $data  Shipping form data.
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function prepareMethodNotification(array $data): array
	{
		$result = [];
		if (empty($data))
		{
			return $result;
		}

		$address_string = $this->addressToString($data);
		if (!empty($address_string))
		{
			$result['PLG_RADICALMART_SHIPPING_STANDARD_SHIPPING_ADDRESS'] = $address_string;
		}
		if (!empty($data['comment']))
		{
			$result['PLG_RADICALMART_SHIPPING_STANDARD_SHIPPING_COMMENT'] = $data['comment'];
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