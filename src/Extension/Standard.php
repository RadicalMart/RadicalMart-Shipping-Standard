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
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
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
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   1.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentNormaliseRequestData'   => 'onContentNormaliseRequestData',
			'onRadicalMartGetShippingMethods' => 'onRadicalMartGetShippingMethods',
			'onRadicalMartGetOrderTotal'      => 'onRadicalMartGetOrderTotal',
			'onRadicalMartGetOrderForm'       => 'onRadicalMartGetOrderForm',
		];
	}

	/**
	 * Prepare prices data.
	 *
	 * @param   Event  $event  The event.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentNormaliseRequestData(Event $event)
	{
		$context = $event->getArgument('0');
		$objData = $event->getArgument('1');
		if ($context === 'com_radicalmart.shippingmethod')
		{
			foreach ($objData->prices as &$price)
			{
				$price['base'] = PriceHelper::clean($price['base'], $price['currency']);
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
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
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

		$price = $this->preparePrice($price, $currency['code']);

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
	 * Prepare order totals.
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
	public function onRadicalMartGetOrderTotal(string $context, array &$total, array $formData, object $shipping,
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
	 * Prepare order form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   Form    $form      Order form object.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderForm(string $context, Form $form, array $formData, object $shipping,
	                                          object $payment)
	{
		// Remove fields
		$fields = ['country', 'city', 'zip', 'street', 'house', 'building', 'entrance', 'floor', 'apartment', 'comment'];
		foreach ($fields as $field)
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
	 * Prepare price values.
	 *
	 * @param   array        $price  Item price array.
	 * @param   string|null  $code   Currency code.
	 *
	 * @throws \Exception
	 *
	 * @return array Formatting price array, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function preparePrice(array $price = [], string $code = null): array
	{
		// Set base price
		$price['base']        = PriceHelper::clean($price['base'], $code);
		$price['base_string'] = (empty($price['base'])) ? Text::_('COM_RADICALMART_PRICE_FREE')
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