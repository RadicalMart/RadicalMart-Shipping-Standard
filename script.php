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

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

return new class () implements ServiceProviderInterface {
	public function register(Container $container)
	{
		$container->set(InstallerScriptInterface::class, value: new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
			/**
			 * The application object
			 *
			 * @var  AdministratorApplication
			 *
			 * @since  1.1.0
			 */
			protected AdministratorApplication $app;

			/**
			 * The Database object.
			 *
			 * @var   DatabaseDriver
			 *
			 * @since  1.1.0
			 */
			protected DatabaseDriver $db;

			/**
			 * Minimum Joomla version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.1.0
			 */
			protected string $minimumJoomla = '4.2';

			/**
			 * Minimum PHP version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  1.1.0
			 */
			protected string $minimumPhp = '7.4';

			/**
			 * Update methods.
			 *
			 * @var  array
			 *
			 * @since  3.0.0
			 */
			protected array $updateMethods = [
				'update3_0_0',
			];

			/**
			 * Constructor.
			 *
			 * @param   AdministratorApplication  $app  The application object.
			 *
			 * @since 1.1.0
			 */
			public function __construct(AdministratorApplication $app)
			{
				$this->app = $app;
				$this->db  = Factory::getContainer()->get('DatabaseDriver');
			}

			/**
			 * Function called after the extension is installed.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.1.0
			 */
			public function install(InstallerAdapter $adapter): bool
			{
				$this->enablePlugin($adapter);

				return true;
			}

			/**
			 * Function called after the extension is updated.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.1.0
			 */
			public function update(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called after the extension is uninstalled.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.1.0
			 */
			public function uninstall(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called before extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.1.0
			 */
			public function preflight(string $type, InstallerAdapter $adapter): bool
			{
				// Check compatible
				if (!$this->checkCompatible())
				{
					return false;
				}

				return true;
			}

			/**
			 * Function called after extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   1.1.0
			 */
			public function postflight(string $type, InstallerAdapter $adapter): bool
			{
				$installer = $adapter->getParent();
				if ($type !== 'uninstall')
				{
					// Parse layouts
					$this->parseLayouts($installer->getManifest()->layouts, $installer);

					// Run updates script
					if ($type === 'update')
					{
						foreach ($this->updateMethods as $method)
						{
							if (method_exists($this, $method))
							{
								$this->$method($adapter);
							}
						}
					}
				}
				else
				{
					// Remove layouts
					$this->removeLayouts($installer->getManifest()->layouts);
				}

				return true;
			}

			/**
			 * Method to check compatible.
			 *
			 * @throws  \Exception
			 *
			 * @return  bool True on success, False on failure.
			 *
			 * @since  1.1.0
			 */
			protected function checkCompatible(): bool
			{
				$app = Factory::getApplication();

				// Check joomla version
				if (!(new Version())->isCompatible($this->minimumJoomla))
				{
					$app->enqueueMessage(Text::sprintf('PLG_RADICALMART_SHIPPING_STANDARD_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
						'error');

					return false;
				}

				// Check PHP
				if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
				{
					$app->enqueueMessage(Text::sprintf('PLG_RADICALMART_SHIPPING_STANDARD_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
						'error');

					return false;
				}

				return true;
			}

			/**
			 * Enable plugin after installation.
			 *
			 * @param   InstallerAdapter  $adapter  Parent object calling object.
			 *
			 * @since  1.1.0
			 */
			protected function enablePlugin(InstallerAdapter $adapter)
			{
				// Prepare plugin object
				$plugin          = new \stdClass();
				$plugin->type    = 'plugin';
				$plugin->element = $adapter->getElement();
				$plugin->folder  = (string) $adapter->getParent()->manifest->attributes()['group'];
				$plugin->enabled = 1;

				// Update record
				$this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);
			}

			/**
			 * Method to parse through a layouts element of the installation manifest and take appropriate action.
			 *
			 * @param   SimpleXMLElement|null  $element    The XML node to process.
			 * @param   Installer|null         $installer  Installer calling object.
			 *
			 * @return  bool  True on success.
			 *
			 * @since  1.1.0
			 */
			public function parseLayouts(SimpleXMLElement $element = null, Installer $installer = null): bool
			{
				if (!$element || !count($element->children()))
				{
					return false;
				}

				// Get destination
				$folder      = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
				$destination = Path::clean(JPATH_ROOT . '/layouts' . $folder);

				// Get source
				$folder = (string) $element->attributes()->folder;
				$source = ($folder && file_exists($installer->getPath('source') . '/' . $folder))
					? $installer->getPath('source') . '/' . $folder : $installer->getPath('source');

				// Prepare files
				$copyFiles = [];
				foreach ($element->children() as $file)
				{
					$path['src']  = Path::clean($source . '/' . $file);
					$path['dest'] = Path::clean($destination . '/' . $file);

					// Is this path a file or folder?
					$path['type'] = $file->getName() === 'folder' ? 'folder' : 'file';
					if (basename($path['dest']) !== $path['dest'])
					{
						$newdir = dirname($path['dest']);
						if (!Folder::create($newdir))
						{
							Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

							return false;
						}
					}

					$copyFiles[] = $path;
				}

				return $installer->copyFiles($copyFiles, true);
			}

			/**
			 * Method to parse through a layouts element of the installation manifest and remove the files that were installed.
			 *
			 * @param   SimpleXMLElement|null  $element  The XML node to process.
			 *
			 * @return  bool  True on success.
			 *
			 * @since  1.1.0
			 */
			protected function removeLayouts(SimpleXMLElement $element = null): bool
			{
				if (!$element || !count($element->children()))
				{
					return false;
				}

				// Get the array of file nodes to process
				$files = $element->children();

				// Get source
				$folder = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
				$source = Path::clean(JPATH_ROOT . '/layouts' . $folder);

				// Process each file in the $files array (children of $tagName).
				foreach ($files as $file)
				{
					$path = Path::clean($source . '/' . $file);

					// Actually delete the files/folders
					if (is_dir($path))
					{
						$val = Folder::delete($path);
					}
					else
					{
						$val = File::delete($path);
					}

					if ($val === false)
					{
						Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

						return false;
					}
				}

				if (!empty($folder))
				{
					Folder::delete($source);
				}

				return true;
			}

			/**
			 * Method to update to 3.0.0 version.
			 *
			 * @since  3.0.0
			 */
			protected function update3_0_0()
			{
				$db     = $this->db;
				$fields = [
					'field_country'   => true,
					'field_city'      => true,
					'field_zip'       => true,
					'field_street'    => true,
					'field_house'     => true,
					'field_building'  => false,
					'field_entrance'  => false,
					'field_floor'     => false,
					'field_apartment' => false,
					'field_comment'   => false,
				];
				if (!empty(ComponentHelper::getComponent('com_radicalmart')->id))
				{
					$query   = $db->getQuery(true)
						->select(['id', 'params'])
						->from($db->quoteName('#__radicalmart_shipping_methods'))
						->where($db->quoteName('plugin') . ' = ' . $db->quote('standard'));
					$methods = $db->setQuery($query)->loadObjectList();
					foreach ($methods as $method)
					{
						$method->params = new Registry($method->params);
						foreach ($fields as $path => $required)
						{
							$value = $method->params->get($path);
							if (!is_numeric($value))
							{
								continue;
							}

							if ((int) $value === 0)
							{
								$value = 'hidden';
							}
							else
							{
								$value = ($required) ? 'required' : 'not_required';
							}

							$method->params->set($path, $value);
						}
						$method->params = $method->params->toString();
						$db->updateObject('#__radicalmart_shipping_methods', $method, 'id');
					}
				}

				if (!empty(ComponentHelper::getComponent('com_radicalmart_express')->id))
				{
					$query          = $db->getQuery(true)
						->select(['extension_id', 'params'])
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('element') . ' = ' . $db->quote('com_radicalmart_express'));
					$update         = $db->setQuery($query, 0, 1)->loadObject();
					$update->params = (new Registry($update->params))->toArray();
					if (!empty($update->params['shipping_method_plugin']) && $update->params['shipping_method_plugin'] === 'standard')
					{
						if (!isset($update->params['shipping_method_params']))
						{
							$update->params['shipping_method_params'] = [];
						}
						foreach ($fields as $path => $required)
						{
							if (isset($update->params['shipping_method_params'][$path]))
							{
								$value = $update->params['shipping_method_params'][$path];
								if (!is_numeric($value))
								{
									continue;
								}

								if ((int) $value === 0)
								{
									$value = 'hidden';
								}
								else
								{
									$value = ($required) ? 'required' : 'not_required';
								}

								$update->params['shipping_method_params'][$path] = $value;
							}
						}
					}
					$update->params = (new Registry($update->params))->toString();
					$db->updateObject('#__extensions', $update, 'extension_id');
				}
			}
		});
	}
};