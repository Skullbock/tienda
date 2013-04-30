<?php
/**
 * @version	1.5
 * @package	Tienda
 * @author 	Daniele Rosario
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

Tienda::load('TiendaToolPlugin', 'library.plugins.tool');

class plgTiendaTool_NetsuiteCsvImporter extends TiendaToolPlugin {
	
	/**
	 * @var $_element  string  Should always correspond with the plugin's filename,
	 *                         forcing it to be unique
	 */
	var $_element = 'tool_netsuitecsvimporter';

	protected $columns = array(
		'netsuite_id',
		'name',
		'display_name',
		'category',
		'parent_category',
		'parent',
		'type',
		'price',
		'quantity',
		'image',
		'currency',
		'description',
		'other_image',
		'necklace_closure',
		'earring_closure',
		'dimensions',
		'color',
		'size'
	);

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$language = JFactory::getLanguage();
		$language -> load('plg_tienda_' . $this -> _element, JPATH_ADMINISTRATOR, 'en-GB', true);
		$language -> load('plg_tienda_' . $this -> _element, JPATH_ADMINISTRATOR, null, true);

		$this->checkInstallation();
	}

	/**
	 * Overriding
	 *
	 * @param $options
	 * @return unknown_type
	 */
	function onGetToolView($row) {
		if (!$this -> _isMe($row)) {
			return null;
		}

		// go to a "process suffix" method
		// which will first validate data submitted,
		// and if OK, will return the html?
		$suffix = $this -> _getTokenSuffix();
		$html = $this -> _processSuffix($suffix);

		return $html;
	}



	/**
	 * Validates the data submitted based on the suffix provided
	 *
	 * @param $suffix
	 * @return html
	 */
	function _processSuffix($suffix = '') {
		$html = "";

		switch($suffix) {
			case"2" :
				if (!$this->loadFile()) {
					JError::raiseNotice('loadFile', $this -> getError());
					$html .= $this -> _renderForm('1');
				} else {
					// migrate the data and output the results
					$html .= $this -> doMigration();
				}
				break;
			case"1" :
				if (!$this->loadFile()) {
					JError::raiseNotice('loadFile', $this -> getError());
					$html .= $this -> _renderForm('1');
				} else {
					$suffix++;

					$vars = new JObject();
					$vars -> header = $this->columns;
					$vars -> preview = $this->getPreview();
					$vars -> state = $this -> _getState();
					$vars -> total_records = $this -> getTotalRecords();
					$vars -> setError($this -> getError());

					// display a 'connection verified' message
					// and request confirmation before migrating data
					$html .= $this -> _renderForm($suffix, $vars);
					$html .= $this -> _renderView($suffix, $vars);
				}
				break;
			default :
				$html .= $this -> _renderForm('1');
				break;
		}

		return $html;
	}

	/**
	 * Prepares the 'view' tmpl layout
	 *
	 * @return unknown_type
	 */
	function _renderView($suffix = '', $vars = 0) {
		if (!$vars) {
			$vars = new JObject();
		}
		$layout = 'view_' . $suffix;
		$html = $this -> _getLayout($layout, $vars);

		return $html;
	}

	/**
	 * Prepares variables for the form
	 *
	 * @return unknown_type
	 */
	function _renderForm($suffix = '', $vars = 0) {
		if (!$vars) {
			$vars = new JObject();
			$vars -> state = $this -> _getState();
		}
		$vars -> token = $this -> _getToken($suffix);

		$layout = 'form_' . $suffix;
		$html = $this -> _getLayout($layout, $vars);

		return $html;
	}

	/**
	 * Load the CSV file, uploading it if necessary
	 */
	function loadFile() {
		$state = $this -> _getState();

		// Uploads the file
		Tienda::load('TiendaFile', 'library.file');
		$upload = new TiendaFile();

		// we have to upload the file
		if (@$state -> uploaded_file == '') {
			// handle upload creates upload object properties
			$success = $upload -> handleUpload('file');

			if ($success) {
				if (strtolower($upload -> getExtension()) != 'csv') {
					$this -> setError(JText::_('COM_TIENDA_THIS_IS_NOT_A_CSV_FILE'));
					return false;
				}

				// Move the file to let us reuse it
				$upload -> setDirectory(JFactory::getConfig() -> get('tmp_path', JPATH_SITE . DS . 'tmp'));
				$success = $upload -> upload();

				if (!$success) {
					$this -> setError($upload -> getError());
					return false;
				}

				$this->uploaded_file = $upload -> file_path = $upload -> getFullPath();
			} else {
				$this -> setError(JText::_('COM_TIENDA_COULD_NOT_UPLOAD_CSV_FILE' . $upload -> getError()));
				return false;
			}
		}
		// File already uploaded
		else {
			$this->uploaded_file = $upload -> full_path = $upload -> file_path = @$state -> uploaded_file;
			$success = true;
		}

		return $success;
	}

	/**
	 * Get the total number of records in the CSV file smartly
	 */
	protected function getTotalRecords()
	{
		$state = $this -> _getState();
		$file = fopen($state->uploaded_file,'rb');

		if (!$file) {
			$this->setError('COM_TIENDA_COULD_NOT_READ_CSV_FILE');
			return false;
		}
		
		// Let's save RAM. Don't dump the entire csv into memory, but load by row and count the rows
		$lines = 0;
		while (fgets($file) !== false) $lines++;
		fclose($file);

		if (@$state->skip_first) {
			$lines--;
		}

		return $lines;
	}

	/**
	 * Get the first 10 rows of the csv file
	 */
	protected function getPreview() 
	{
		return $this->getRows(0, 10);
	}

	/**
	 * Ajax method to proxy the importing
	 */
	public function ajaxImport()
	{
		$state = $this->_getState();
		$start = JRequest::getVar('start', 0);
		$total = JRequest::getVar('total', false);
		$limit = JRequest::getVar('limit', 25);

		$records = $this->getRows($start, $limit);

		$this->import($records);
		
		foreach ($records as $record) {
			$record = new DSCParameter($record);
			$record = $this->mapTiendaFields($record);
		}

		$processed = count($records);

		return $processed;
	}

	/**
	 * Map the fields in the csv to the tienda ones
	 */
	protected function mapTiendaFields($record) 
	{
		$data = array();

		$name = $this->getProductName($record);
		if ($name) {
			$data['product_name'] 		= $name;
		}

		$sku = $record->get('name', '');
		if ($sku) {
			$data['product_sku'] 		= $sku;
		}

		$category = $this->getCategory($record);
		if ($category) {
			$data['product_category']	= array($category);
		}

		$price = $record->get('price', 0);
		if ($price) {
			$data['product_price'] 		= $price;
		}

		$image = $record->get('image', '');
		if ($image) {
			$data['product_full_image']	= $image;
		}

		$q = $record->get('quantity', 0);
		if ($q) {
			$data['product_quantity'] = $q;
		} 

		return $data;
	}

	/**
	 * Get the product name
	 */
	protected function getProductName($record)
	{
		$name = $record->get('display_name', $record->get('name', ''));
		
		// Split : netsuite way of parent->child
		if ($record->get('parent', false) && stripos($name, ":")) {
			$parts = explode(":", $name);
			$name = trim($parts[count($parts) - 1]);
		}

		return $name;
	}

	/**
	 * Get the category id of the product, creating the category tree if necessary
	 */
	protected function getCategory($record)
	{
		$category = $record->get('category', '');

		if ($record->get('parent_category', false) && stripos($category, ":")) {
			$tree = explode(":", $category);
		} else {
			$tree = array($category);
		}

		foreach ($tree as &$t) {
			$t = trim($t);
		}

		// Create category tree
		$parent = 1;
		foreach ($tree as $c) {
			// check for existance
			JModel::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_tienda' . DS . 'models');
			$model = JModel::getInstance('Categories', 'TiendaModel');
			$model -> setState('filter_name', $c);
			$matches = $model->getList();
			$matched = false;

			if ($matches) {
				foreach ($matches as $match) {
					// is a perfect match?
					if (strtolower($c) == strtolower($match->category_name)) {
						$category_id = $match->category_id;
						$matched = true;
					}
				}
			}

			$category = JTable::getInstance('Categories', 'TiendaTable');

			// Update only if necessary
			if ($matched) {
				
				$category->load($category_id);

				if ($category->category_name == $c && $category->parent_id == $parent) {
					$update = false;
					$parent = $category->category_id;
				} else {
					$update = true;
				}
			} else {
				$update = true;
			}

			if ($update) {
				$category->category_name = $c;
				$category->parent_id = $parent;
				$category->category_enabled = 1;
				$category->save();
			}
			
			$parent = $category->category_id;
		}

		return (int) $parent;
	}

	/**
	 * The real importing is done here
	 */
	protected function import($records)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_tienda' . DS . 'tables');
		JTable::addIncludePath(dirname(__FILE__) . DS . $this->_element . DS . 'tables');

		// Loop though the rows
		foreach ($records as $record) {

			$record = new DSCParameter($record);

			if ($record->get('parent', false)) {
				$this->importAttribute($record);
			} else {
				$this->importProduct($record);
			}
		}
	}

	/**
	 * Import the product from  the csv record
	 * @param  [type] $record [description]
	 * @return [type]         [description]
	 */
	protected function importProduct($record)
	{
		// First: Netsuite ID check. It was already imported?
		$netsuite_id = $record->get('netsuite_id');
	
		$nxref = $this->getNetsuiteXref($netsuite_id);

		$product = JTable::getInstance('Products', 'TiendaTable');
		
		$isNew = true;

		// Update!
		if (isset($nxref->product_id)) {
			$product_id = $nxref->product_id;
			$isNew = false;
		}

		$data = $this->mapTiendaFields($record);

		// If is a new product, use product->create()
		if ($isNew) {
			$product -> product_price = 0;
			$product -> product_check_inventory = 1;
			$product -> bind($data);
			$product -> product_category = $data['product_category'];

			$product -> create();

			// Save netsuite xref
			$this->saveXref($netsuite_id, $product->product_id);
		}
		// else use the save() method
		else {
			$product->load($product_id);
			$product -> bind($data);

			//check if normal price exists
			Tienda::load("TiendaHelperProduct", 'helpers.product');
			$helper = new TiendaHelperProduct();
			$prices = $helper->getPrices($product -> product_id);
			$quantities = $helper->getProductQuantities($product -> product_id);

			if ($product -> save()) {

				if (isset($data['product_price'])) {
					// New price?
					if (empty($prices)) {
						// set price if new or no prices set
						$price = JTable::getInstance('Productprices', 'TiendaTable');
						$price -> product_id = $product -> id;
						$price -> product_price = $data['product_price'];
						$price -> group_id = Tienda::getInstance() -> get('default_user_group', '1');
						$price -> save();
					}
					// Overwrite price
					else {
						// set price if new or no prices set
						$price = JTable::getInstance('Productprices', 'TiendaTable');
						$price -> load($prices[0] -> product_price_id);
						$price -> product_price = $data['product_price'];
						$price -> group_id = Tienda::getInstance() -> get('default_user_group', '1');
						$price -> save();
					}
				}

				if (isset($data['product_quantity'])) {
					// New quantity?
					if (empty($quantities)) {
						// save default quantity
						$quantity = JTable::getInstance('Productquantities', 'TiendaTable');
						$quantity -> product_id = $product -> id;
						$quantity -> quantity = $data['product_quantity'];
						$quantity -> save();
					}
					// Overwrite Quantity
					else {
						// save default quantity
						$quantity = JTable::getInstance('Productquantities', 'TiendaTable');
						$quantity -> load(array('product_id' => $product->product_id, 'product_attributes' => ''));
						$quantity -> product_id = $product -> product_id;
						$quantity -> quantity = $data['product_quantity'];
						$quantity -> save();
					}
				}

			}

			// at this point, the product is saved, so now do additional relationships

			// such as categories
			if (!empty($product->product_id) && isset($data['product_category'])) {

				$data['product_category'] = (array) $data['product_category'];

				foreach ($data['product_category'] as $category_id) {
					// save xref in every case
					$xref = JTable::getInstance('ProductCategories', 'TiendaTable');
					$xref->load(array('product_id' => $product_id, 'category_id' => $category_id));
					$xref->product_id = $product->product_id;
					$xref->category_id = $category_id;
					$xref->save();
				}
			}
			//$this -> _migrateImages($product -> product_id, $data['product_images'], $results);
		}
	}

	/**
	 * Import a product attribute from a record
	 */
	protected function importAttribute($record, $attribute = 'Color') 
	{
		// Get the parent
		$parent = $record->get('parent');
		$model = JModel::getInstance('Products', 'TiendaModel');
		$model->setState('filter_sku', $parent);
		$list = $model->getList();

		$product = false;
		foreach ($list as $p) {
			if ($p->product_sku == $parent) {
				$product = $p;
			}
		}
		
		if (!$product) {
			return false;
		}

		$parent = $product->product_id;

		// Add the Attribute
		$table = JTable::getInstance('ProductAttributes', 'TiendaTable');
		$table->load(array('product_id' => $parent, 'productattribute_name' => $attribute));
		
		if (!isset($table->productattribute_id) || !$table->productattribute_id) {
			$table -> product_id = $parent;
			$table -> productattribute_name = $attribute;
			$table -> save();
		}
		
		// Add the Options for this attribute
		$id = $table -> productattribute_id;

		$model->clearCache();

		// Go for the option
		if ($id) {
			$otable = JTable::getInstance('ProductAttributeOptions', 'TiendaTable');

			$nxref = $this->getNetsuiteXref($record->get('netsuite_id'));

			if (isset($nxref->option_id) && $nxref->option_id) {
				$otable->load($nxref->option_id);
			}

			$otable -> productattribute_id = $id;

			$color = $record->get('color', $this->getProductName($record));
			if ($color) {
				$otable -> productattributeoption_name = $color;
			}

			$price = $record->get('price', 0);
			if ($price) {
				$otable -> productattributeoption_price = $price;
				$otable -> productattributeoption_prefix = '=';
			}

			$otable -> save();

			$option_id = $otable->productattributeoption_id;

			// Save xref
			$this->saveXref($record->get('netsuite_id'), $parent, $option_id);
			
			// And the values
			if ($option_id) {

				// Override values for the variations
				$values = array(
					'product_full_image' => $record->get('other_image', ''),
					'product_model' => $record->get('name')
				);

				foreach ($values as $k => $v) {
					if ($v) {
						$vtable = JTable::getInstance('ProductAttributeOptionValues', 'TiendaTable');
						$vtable->load(array('productattributeoption_id' => $option_id, 'productattributeoptionvalue_field' => $k));

						$vtable -> productattributeoption_id = $option_id;
						$vtable -> productattributeoptionvalue_field = $k;
						$vtable -> productattributeoptionvalue_operator = 'replace'; 
						$vtable -> productattributeoptionvalue_value = $v;
						$vtable -> save();
					}
				}

				$quantity = $record->get('quantity', 0);
				if ($quantity) {
					$qhelper = new TiendaHelperProduct();

					// Renconcile First
					$model->clearCache();
					$qhelper->doProductQuantitiesReconciliation($parent);
					$model->clearCache();

					// Deal with quantities
					$qtable = JTable::getInstance('ProductQuantities', 'TiendaTable');
					$qtable->load(array('product_id' => $parent, 'product_attributes' => $option_id));
					$qtable->product_id = $parent;
					$qtable->product_attributes = $option_id;
					$qtable->quantity = $quantity;
					$qtable->save();
				}
			}
		}
	}

	protected function saveXref($netsuite_id, $product_id, $option_id = 0)
	{
		$table = JTable::getInstance('Netsuiteproducts', 'TiendaTable');
		$table->load(array('product_id' => $product_id, 'netsuite_id' => $netsuite_id, 'option_id' => $option_id));
		$table->netsuite_id = $netsuite_id;
		$table->product_id = $product_id;
		$table->option_id = $option_id;

		$table->save();
	}

	protected function getNetsuiteXref($netsuite_id) 
	{
		$table = JTable::getInstance('Netsuiteproducts', 'TiendaTable');
		$table->load((int)$netsuite_id);

		if (isset($table->product_id)) {
			return $table;
		}

		return false;
	}

	/**
	 * Get a set of rows from the csv smartly
	 */
	protected function getRows($start = 0, $limit = 25 )
	{
		$state = $this->_getState();

		if (@$state->skip_first) {
			$start++;
		}

		$file = fopen($state->uploaded_file,'rb');

		if (!$file) {
			$this->setError('COM_TIENDA_COULD_NOT_READ_CSV_FILE');
			return false;
		}

		// Skip lines
		$i = 0;
		while ($i < $start &&  fgets($file) !== false) {$i++;}

		// Read the right lines
		$i = 0;
		$records = array();
		while (($record = fgetcsv($file)) !== false && $i < $limit) {
			$records[] = $this->_mapFields($record);
			$i++;
		}

		fclose($file);

		return $records;
	}

	/**
	 * Maps the parsed array to an associative array
	 * using the _keys var, for better usability
	 *
	 * @param array $fields
	 */
	function _mapFields($fields) {
		$record = array();
		foreach ($this->columns as $k => $c) {
			$record[$c] = $fields[$k];
		}

		return $record;
	}

	/**
	 * Gets the appropriate values from the request
	 *
	 * @return JObject
	 */
	function _getState() {
		$state = new JObject();
		$state -> file = '';
		$state -> uploaded_file = isset($this->uploaded_file) ? $this->uploaded_file : '';
		$state -> skip_first = 0;

		foreach ($state->getProperties() as $key => $value) {
			$new_value = JRequest::getVar($key);
			$value_exists = array_key_exists($key, JRequest::get('post'));
			if ($value_exists && !empty($key)) {
				$state -> $key = $new_value;
			}
		}

		return $state;
	}

	/**
	 * Perform the data migration
	 *
	 * @return html
	 */
	protected function doMigration() {
		$html = "";
		$vars = new JObject();

		// perform the data migration
		// grab all the data and insert it into the tienda tables
		$state = $this -> _getState();
		$vars -> total_records = $this->getTotalRecords();
		$vars -> state = $this -> _getState();

		$suffix = $this -> _getTokenSuffix();
		$suffix++;
		$layout = 'view_' . $suffix;

		$html = $this -> _getLayout($layout, $vars);
		return $html;
	}

	/**
	 * Migrate the images
	 *
	 * @param int $product_id
	 * @param array $images
	 * @param array $images
	 */
	private function _migrateImages($product_id, $images, $results) {
		Tienda::load('TiendaImage', 'library.image');

		foreach ($images as $image) {
			$check = false;
			$multiple = false;

			if (JURI::isInternal($image)) {
				$internal = true;
				$image = JPATH_SITE . DS . $image;
				if (is_dir($image)) {

					$check = JFolder::exists($image);
					$multiple = true;
				} else {
					$check = JFile::exists($image);
				}
			} else {
				$internal = false;
				$check = $this -> url_exists($image);
			}

			// Add a single image
			if (!$multiple) {
				$images_to_copy = array($image);
			} else {

				// Fetch the images from the folder and add them
				$images_to_copy = Tienda::getClass("TiendaHelperProduct", 'helpers.product') -> getGalleryImages($image);
				foreach ($images_to_copy as &$i) {
					$i = $image . DS . $i;
				}
			}

			if ($check) {
				foreach ($images_to_copy as $image_to_copy) {
					if ($internal) {
						$img = new TiendaImage($image_to_copy);
					} else {
						$tmp_path = JFactory::getApplication() -> getCfg('tmp_path');
						$file = fopen($image_to_copy, 'r');
						$file_content = stream_get_contents($file);
						fclose($file);

						$file = fopen($tmp_path . DS . $image_to_copy, 'w');

						fwrite($file, $file_content);

						fclose($file);

						$img = new TiendaImage($tmp_path . DS . $image_to_copy);
					}

					Tienda::load('TiendaTableProducts', 'tables.products');
					$product = JTable::getInstance('Products', 'TiendaTable');

					$product -> load($product_id);
					$path = $product -> getImagePath();
					$type = $img -> getExtension();

					$img -> load();
					$img -> setDirectory($path);
					// Save full Image
					$img -> save($path . $img -> getPhysicalName());

					// Save Thumb
					Tienda::load('TiendaHelperImage', 'helpers.image');
					$imgHelper = TiendaHelperBase::getInstance('Image', 'TiendaHelper');
					$imgHelper -> resizeImage($img, 'product');

				}
			}
		}

	}

	/**
	 * Do the migration
	 *
	 * @return array
	 */
	function _migrate($datas) {
		$queries = array();

		$results = array();
		$n = 0;

		// Loop though the rows
		foreach ($datas as $data) {
			// Check for product_name. Explode() could have generated an empty row
			if (!empty($data['product_name'])) {
				$isNew = false;

				if (!$data['product_id']) {
					$data['product_id'] = 0;
					$isNew = true;
				}

				JTable::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_tienda' . DS . 'tables');
				$product = JTable::getInstance('Products', 'TiendaTable');

				if (!$isNew) {
					if (!$product -> load($data['product_id'])) {
						$isNew = true;
						$data['product_id'] = 0;
					}
				}

				// If is a new product, use product->create()
				if ($isNew) {
					$product -> product_price = 0;
					$product -> product_quantity = 0;
					$product -> bind($data);

					if ($product -> product_full_image) {
						Tienda::load('TiendaFile', 'library.file');
						// Do the same cleaning to the image title that the image helper does
						$name = explode('.', $product -> product_full_image);
						$name = TiendaFile::cleanTitle($name[0]) . '.' . $name[count($name) - 1];

						$product -> product_full_image = $name;
					}

					$product -> create();

					$this -> _migrateAttributes($product -> product_id, $data['product_attributes']);
				}
				// else use the save() method
				else {
					$product -> bind($data);

					//check if normal price exists
					Tienda::load("TiendaHelperProduct", 'helpers.product');
					$prices = TiendaHelperProduct::getPrices($product -> product_id);
					$quantities = TiendaHelperProduct::getProductQuantities($product -> product_id);

					if ($product -> save()) {
						$product -> product_id = $product -> id;

						// New price?
						if (empty($prices)) {
							// set price if new or no prices set
							$price = JTable::getInstance('Productprices', 'TiendaTable');
							$price -> product_id = $product -> id;
							$price -> product_price = $data['product_price'];
							$price -> group_id = Tienda::getInstance() -> get('default_user_group', '1');
							$price -> save();
						}
						// Overwrite price
						else {
							// set price if new or no prices set
							$price = JTable::getInstance('Productprices', 'TiendaTable');
							$price -> load($prices[0] -> product_price_id);
							$price -> product_price = $data['product_price'];
							$price -> group_id = Tienda::getInstance() -> get('default_user_group', '1');
							$price -> save();
						}

						// New quantity?
						if (empty($quantities)) {
							// save default quantity
							$quantity = JTable::getInstance('Productquantities', 'TiendaTable');
							$quantity -> product_id = $product -> id;
							$quantity -> quantity = $data['product_quantity'];
							$quantity -> save();
						}
						// Overwrite Quantity
						else {
							// save default quantity
							$quantity = JTable::getInstance('Productquantities', 'TiendaTable');
							$quantity -> load($quantities[0] -> productquantity_id);
							$quantity -> product_id = $product -> id;
							$quantity -> quantity = $data['product_quantity'];
							$quantity -> save();
						}

					}

				}

				// at this point, the product is saved, so now do additional relationships

				// such as categories
				if (!empty($product -> product_id) && !empty($data['product_categories'])) {
					foreach ($data['product_categories'] as $category_id) {
						// This is probably not the best way to do it
						// Numeric = id, string = category name
						if (!is_numeric($category_id)) {
							// check for existance
							JModel::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_tienda' . DS . 'models');
							$model = JModel::getInstance('Categories', 'TiendaModel');
							$model -> setState('filter_name', $category_id);
							$matches = $model -> getList();
							$matched = false;

							if ($matches) {
								foreach ($matches as $match) {
									// is a perfect match?
									if (strtolower($category_id) == strtolower($match -> category_name)) {
										$category_id = $match -> category_id;
										$matched = true;
									}
								}
							}

							// Not matched, create category
							if (!$matched) {
								$category = JTable::getInstance('Categories', 'TiendaTable');
								$category -> category_name = $category_id;
								$category -> parent_id = 1;
								$category -> category_enabled = 1;
								$category -> save();

								$category_id = $category -> category_id;
							}

						}

						// save xref in every case
						$xref = JTable::getInstance('ProductCategories', 'TiendaTable');
						$xref -> product_id = $product -> product_id;
						$xref -> category_id = $category_id;
						$xref -> save();
					}
				}

				$results[$n] -> title = $product -> product_name;
				$results[$n] -> query = "";
				$results[$n] -> error = implode('\n', $product -> getErrors());
				$results[$n] -> affectedRows = 1;

				$n++;

				$this -> _migrateImages($product -> product_id, $data['product_images'], $results);

			}

		}

		return $results;
	}

	/**
	 * Migrate a single product attributes
	 *
	 * @param TiendaTableProduct $product
	 * @param array $data
	 */
	private function _migrateAttributes($product_id, $attributes) {
		foreach ($attributes as $attribute_name => $options) {
			// Add the Attribute
			$table = JTable::getInstance('ProductAttributes', 'TiendaTable');
			$table -> product_id = $product_id;
			$table -> productattribute_name = $attribute_name;
			$table -> save();

			// Add the Options for this attribute
			$id = $table -> productattribute_id;
			foreach ($options as $option) {
				$otable = JTable::getInstance('ProductAttributeOptions', 'TiendaTable');
				$otable -> productattribute_id = $id;
				$otable -> productattributeoption_name = $option;
				$otable -> save();
			}
		}
	}

	/**
	 * Checks if the URL exists
	 * @param string $url
	 */
	private function url_exists($url) {
		$url = str_replace("http://", "", $url);
		if (strstr($url, "/")) {
			$url = explode("/", $url, 2);
			$url[1] = "/" . $url[1];
		} else {
			$url = array($url, "/");
		}

		$fh = fsockopen($url[0], 80);
		if ($fh) {
			fputs($fh, "GET " . $url[1] . " HTTP/1.1\nHost:" . $url[0] . "\n\n");
			if (fread($fh, 22) == "HTTP/1.1 404 Not Found") {
				return FALSE;
			} else {
				return TRUE;
			}

		} else {
			return FALSE;
		}
	}

	/**
	 * Adds required xref table to the db on first installation
	 */
	private function checkInstallation()
    {
        // if this has already been done, don't repeat
        if (Tienda::getInstance()->get('checkTableNetsuiteImporter', '0'))
        {
            return true;
        }
        
        $sql = "CREATE TABLE `#__tienda_netsuiteproductsxref` (
			  `netsuite_id` int(11) NOT NULL,
			  `product_id` int(11) NOT NULL,
			  `option_id` int(11) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`netsuite_id`,`product_id`,`option_id`),
			  KEY `netsuite_id` (`netsuite_id`),
			  KEY `product_id` (`product_id`),
			  KEY `option_id` (`option_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
							
        $db = JFactory::getDBO();
        $db->setQuery($sql);
        $result = $db->query();

        $sql = "ALTER TABLE `#__tienda_netsuiteproductsxref`
				  ADD CONSTRAINT `#__tienda_netsuiteproductsxref_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `#__tienda_products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				  ADD CONSTRAINT `#__tienda_netsuiteproductsxref_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `#__tienda_productattributeoptions` (`productattributeoption_id`) ON DELETE CASCADE ON UPDATE CASCADE;";

        $db->setQuery($sql);
        $result_2 = $db->query();

        if ($result && $result_2)
        {
            // Update config to say this has been done already
            JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
            $config = JTable::getInstance( 'Config', 'TiendaTable' );
            $config->load( array( 'config_name'=>'checkTableNetsuiteImporter') );
            $config->config_name = 'checkTableNetsuiteImporter';
            $config->value = '1';
            $config->save();
            return true;
        }

        return false;        
    }

}
