<?php
/**
 * @version	1.5
 * @package	Tienda
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

JLoader::import( 'com_tienda.helpers._base', JPATH_ADMINISTRATOR.DS.'components' );
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class TiendaHelperProduct extends TiendaHelperBase
{
    /**
     * Determines a product's layout 
     * 
     * @param int $product_id
     * @param array options(
     *              'category_id' = if specified, will be used to determine layout if product doesn't have specific one
     *              )
     * @return unknown_type
     */
    function getLayout( $product_id, $options=array() )
    {
        $layout = 'view';
        
        jimport('joomla.filesystem.file');
        $app = JFactory::getApplication();
        $templatePath = JPATH_SITE.DS.'templates'.DS.$app->getTemplate().DS.'html'.DS.'com_tienda'.DS.'products'.DS.'%s'.'.php';

        Tienda::load( 'TiendaTableProducts', 'tables.products' );
        $product = JTable::getInstance( 'Products', 'TiendaTable' );
        $product->load( $product_id );

        // if the product->product_layout file exists in the template, use it
        if (!empty($product->product_layout) && JFile::exists( sprintf($templatePath, $product->product_layout) ))
        {
            return $product->product_layout;
        }
        
        if (!empty($options['category_id']))
        {
            // if the options[category_id] has a layout and it exists, use it
            Tienda::load( 'TiendaTableCategories', 'tables.categories' );
            $category = JTable::getInstance( 'Categories', 'TiendaTable' );
            $category->load( $options['category_id'] );
            if (!empty($category->categoryproducts_layout) && JFile::exists( sprintf($templatePath, $category->categoryproducts_layout) ))
            {
                return $category->categoryproducts_layout;
            }
        }

        // if the product is in a category, try to use the layout from that one 
        $categories = TiendaHelperProduct::getCategories( $product->product_id );
        if (!empty($categories))
        {
            Tienda::load( 'TiendaTableCategories', 'tables.categories' );
            $category = JTable::getInstance( 'Categories', 'TiendaTable' );
            $category->load( $categories[0] ); // load the first category
            if (!empty($category->categoryproducts_layout) && JFile::exists( sprintf($templatePath, $category->categoryproducts_layout) ))
            {
                return $category->categoryproducts_layout;
            }
        }
        
        // TODO if there are multiple categories, which one determines product layout?
        // if the product is in multiple categories, try to use the layout from the deepest category
            // and move upwards in tree after that
            
        // if all else fails, use the default!
        return $layout;
    }
    
	/**
	 * Converts a path string to a URI string
	 * 
	 * @param $path
	 * @return unknown_type
	 */
	function getUriFromPath( $path )
	{
		$path = str_replace(JPATH_SITE.DS, JURI::root(), $path);		
		$path = str_replace(DS, '/', $path);
        return $path;
	}
	
	/**
	 * Returns array of filenames
	 * Array
     * (
     *     [0] => airmac.png
     *     [1] => airportdisk.png
     *     [2] => applepowerbook.png
     *     [3] => cdr.png
     *     [4] => cdrw.png
     *     [5] => cinemadisplay.png
     *     [6] => floppy.png
     *     [7] => macmini.png
     *     [8] => shirt1.jpg
     * )
	 * @param $folder
	 * @return array
	 */
	function getGalleryImages( $folder=null )
	{
		$images = array();
		
		if (empty($folder))
		{
			return $images;
		}
		
        if (JFolder::exists( $folder ))
        {
        	$extensions = array( 'png', 'gif', 'jpg', 'jpeg' );
        	
        	$files = JFolder::files( $folder );
        	foreach ($files as $file)
        	{
	            $namebits = explode('.', $file);
	            $extension = $namebits[count($namebits)-1];
	            if (in_array($extension, $extensions))
	            {
                    $images[] = $file;	
	            }
        	}
        }
        
        return $images;
	}
	
	/**
	 * Returns the full path to the product's image gallery files
	 * 
	 * @param int $id
	 * @return string
	 */
	function getGalleryPath( $id )
	{
		static $paths;
		
		$id = (int) $id;
		
		if (!is_array($paths)) { $paths = array(); }
		
		if (empty($paths[$id]))
		{
			$paths[$id] = '';
			
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
            $row = JTable::getInstance('Products', 'TiendaTable');
            $row->load( (int) $id );
			if (empty($row->product_id))
			{
				// TODO figure out what to do if the id is invalid 
				return null;
			}

			$paths[$id] = $row->getImagePath(false);
		}
		
		return $paths[$id];
	}
	
    /**
	 * Returns the full path to the product's image gallery files
	 * 
	 * @param int $id
	 * @return string
	 */
	function getGalleryUrl( $id )
	{
		static $urls;
		
		$id = (int) $id;
		
		if (!is_array($urls)) { $urls = array(); }
		
		if (empty($urls[$id]))
		{
			$urls[$id] = '';
			
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
            $row = JTable::getInstance('Products', 'TiendaTable');
            $row->load( (int) $id );
			if (empty($row->product_id))
			{
				// TODO figure out what to do if the id is invalid 
				return null;
			}

			$urls[$id] = $row->getImageUrl();
		}
		
		return $urls[$id];
	}
	
    /**
     * Returns the full path to the product's files
     * 
     * @param int $id
     * @return string
     */
    function getFilePath( $id )
    {
        static $paths;
        
        $id = (int) $id;
        
        if (!is_array($paths)) { $paths = array(); }
        
        if (empty($paths[$id]))
        {
            $paths[$id] = '';
            
            JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
            $row = JTable::getInstance('Products', 'TiendaTable');
            $row->load( (int) $id );
            if (empty($row->product_id))
            {
                // TODO figure out what to do if the id is invalid 
                return null;
            }
            
            // if product_images_path is valid and not empty, use it
            if (!empty($row->product_files_path))
            {
                $folder = $row->product_files_path;
                if (JFolder::exists( $folder )) 
                {
                    $files = JFolder::files( $folder );
                    if (!empty($files))
                    {
                        $paths[$id] = $folder;
                    }
                }
            }
            
            // if no override, use path based on sku if it is valid and not empty
            // TODO clean SKU so valid characters used for folder name?
            if (empty($paths[$id]) && !empty($row->product_sku))
            {
                $folder = Tienda::getPath( 'products_files' ).DS.'sku'.DS.$row->product_sku;
                if (JFolder::exists( $folder )) 
                {
                    $files = JFolder::files( $folder );
                    if (!empty($files))
                    {
                        $paths[$id] = $folder;
                    }
                }
            }
            
            // if still unset, use path based on id number
            if (empty($paths[$id]))
            {
                $folder = Tienda::getPath( 'products_files' ).DS.'id'.DS.$row->product_id;
                if (!JFolder::exists( $folder )) 
                {
                    JFolder::create( $folder );
                }
                $paths[$id] = $folder;
            }
        }
        
        // TODO Make sure the files folder has htaccess file
        return $paths[$id];
    }	
	
	/**
	 * 
	 * @param $id
	 * @param $by
	 * @param $alt
	 * @param $type
	 * @param $url
	 * @return unknown_type
	 */
	function getImage( $id, $by='id', $alt='', $type='thumb', $url=false )
	{
		switch($type)
		{
			case "full":
				$path = 'products_images';
			  break;
			case "thumb":
			default:
				$path = 'products_thumbs';
			  break;
		}
		
		$tmpl = "";
		if (strpos($id, '.'))
		{
			// then this is a filename, return the full img tag if file exists, otherwise use a default image
			$src = (JFile::exists( Tienda::getPath( $path ).DS.$id))
				? Tienda::getUrl( $path ).$id : 'media/com_tienda/images/noimage.png';
			
			// if url is true, just return the url of the file and not the whole img tag
			$tmpl = ($url)
				? $src : "<img src='".$src."' alt='".JText::_( $alt )."' title='".JText::_( $alt )."' name='".JText::_( $alt )."' align='center' border='0'>";

		}
			else
		{
			if (!empty($id))
			{
				// load the item, get the filename, create tmpl
				JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
				$row = JTable::getInstance('Products', 'TiendaTable');
				$row->load( (int) $id );
				
				$urli = $row->getImageUrl();
				$dir = $row->getImagePath();
				
				if($path == 'products_thumbs'){
					$dir .= DS.'thumbs';
					$urli .= 'thumbs/';
				}
				
				$file = $dir.DS.$row->product_full_image;
				
				$id = $urli.$row->product_full_image;

				$src = (JFile::exists( $file ))
					? $id : 'media/com_tienda/images/noimage.png';

				$tmpl = ($url)
					? $src : "<img src='".$src."' alt='".JText::_( $alt )."' title='".JText::_( $alt )."' name='".JText::_( $alt )."' align='center' border='0' >";
			}			
		}
		return $tmpl;
	}
	
	/**
	 * Gets a product's list of prices
	 * 
	 * @param $id
	 * @return array
	 */
	function getPrices( $id )
	{
		if (empty($id))
		{
			return array();
		}
		JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
		$model = JModel::getInstance( 'ProductPrices', 'TiendaModel' );
		$model->setState( 'filter_id', $id );
		$items = $model->getList();
		return $items;
	}
	
	/**
	 * Returns a product's price based on the quantity purchased, user's group, and date
	 * 
	 * @param unknown_type $id
	 * @param unknown_type $quantity
	 * @param unknown_type $user_group_id
	 * @param unknown_type $date
	 * @return unknown_type
	 */
	function getPrice( $id, $quantity='1', $user_group_id='', $date='' )
	{
		$price = null;
		if (empty($id))
		{
			return $price;
		}
		JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
		$model = JModel::getInstance( 'ProductPrices', 'TiendaModel' );
		$model->setState( 'filter_id', $id );
		$prices = TiendaHelperProduct::getPrices($id);
		
		(int) $quantity;
		if ($quantity <= '0') { $quantity = '1'; }
			//where price_quantity_start < $quantity
			$model->setState( 'filter_quantity', $quantity );
			
		// does date even matter?
		$nullDate = JFactory::getDBO()->getNullDate();
		if (empty($date) || $date == $nullDate) { $date = JFactory::getDate()->toMysql(); }
			$model->setState( 'filter_date', $date );
			//where product_price_startdate <= $date
			//where product_price_enddate >= $date OR product_price_enddate == nullDate 
			
		// does user_group_id?
		(int) $user_group_id;
		$default_user_group = '0'; /* TODO Use a default $user_group_id */
		if ($user_group_id <= '0') { $user_group_id = $default_user_group; }
			// using ->getPrices(), do a getColumn() on the array for the user_group_id column
			$user_group_ids = TiendaHelperBase::getColumn($prices, 'user_group_id');
			if (in_array($user_group_id, $user_group_ids))
			{
				// if $user_group_id is in the column, then set the query to pull an exact match on it,
				$model->setState( 'filter_user_group', $user_group_id ); 
			} 
				else
			{
				// otherwise, $user_group_id_determined = the default $user_group_id
				$model->setState( 'filter_user_group', $default_user_group );				
			}
		
		$items = $model->getList();
		if (count($items) >= '1')
		{
			// TODO return the first record even if more than one returned?
			$price = $items[0];	
		}
		return $price;	
	}
	
    /**
     * Returns the tax rate for an item
     *  
     * @param int $product_id
     * @param int $geozone_id
     * @return int
     */
    public function getTaxRate( $product_id, $geozone_id )
    {
    	JLoader::import( 'com_tienda.library.query', JPATH_ADMINISTRATOR.DS.'components' );
            
        $taxrate = "0.00000";
        $db = JFactory::getDBO();
        
        $query = new TiendaQuery();
        $query->select( 'tbl.*' );
        $query->from('#__tienda_taxrates AS tbl');
        $query->join('LEFT', '#__tienda_products AS product ON product.tax_class_id = tbl.tax_class_id');
        $query->where('product.product_id = '.$product_id);
        $query->where('tbl.geozone_id = '.$geozone_id);
        
        $db->setQuery( (string) $query );
        if ($data = $db->loadObject())
        {
        	$taxrate = $data->tax_rate;
        }
        
        return $taxrate;
    }
	
	/**
	 * Gets a product's list of categories
	 * 
	 * @param $id
	 * @return array
	 */
	function getCategories( $id )
	{
		if (empty($id))
		{
			return array();
		}
		JLoader::import( 'com_tienda.library.query', JPATH_ADMINISTRATOR.DS.'components' );
		JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
		$table = JTable::getInstance( 'ProductCategories', 'TiendaTable' );
		
		$query = new TiendaQuery();
		$query->select( "tbl.category_id" );
		$query->from( $table->getTableName()." AS tbl" );
		$query->where( "tbl.product_id = ".(int) $id );
		$db = JFactory::getDBO();
		$db->setQuery( (string) $query );
		$items = $db->loadResultArray();
		return $items;
	}
	
	/**
	 * Returns a list of a product's attributes
	 * 
	 * @param unknown_type $id
	 * @return unknown_type
	 */
    function getAttributes( $id )
    {
        if (empty($id))
        {
            return array();
        }
        JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
        $model = JModel::getInstance( 'ProductAttributes', 'TiendaModel' );
        $model->setState( 'filter_product', $id );
        $items = $model->getList();
        return $items;
    }
    
    /**
     * Returns a list of a product's files
     * 
     * @param unknown_type $id
     * @return unknown_type
     */
    function getFiles( $id )
    {
        if (empty($id))
        {
            return array();
        }
        JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
        $model = JModel::getInstance( 'ProductFiles', 'TiendaModel' );
        $model->setState( 'filter_product', $id );
        $items = $model->getList();
        return $items;
    }
	
    /**
     * Finds the prev & next items in the list 
     *  
     * @param $id   product id
     * @return array( 'prev', 'next' )
     */
    function getSurrounding( $id )
    {
        $return = array();
        
        $prev = intval( JRequest::getVar( "prev" ) );
        $next = intval( JRequest::getVar( "next" ) );
        if ($prev || $next) 
        {
            $return["prev"] = $prev;
            $return["next"] = $next;
            return $return;
        }
        
        $app = JFactory::getApplication();
        JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
        $model = JModel::getInstance( 'Products', 'TiendaModel' );
        $ns = $app->getName().'::'.'com.tienda.model.'.$model->getTable()->get('_suffix');
        $state = array();
        
        $config = TiendaConfig::getInstance();
        
        $state['limit']     = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $state['limitstart'] = $app->getUserStateFromRequest($ns.'limitstart', 'limitstart', 0, 'int');
        $state['filter']    = $app->getUserStateFromRequest($ns.'.filter', 'filter', '', 'string');
        $state['order']     = $app->getUserStateFromRequest($ns.'.filter_order', 'filter_order', 'tbl.'.$model->getTable()->getKeyName(), 'cmd');
        $state['direction'] = $app->getUserStateFromRequest($ns.'.filter_direction', 'filter_direction', 'ASC', 'word');
                
        $state['filter_id_from']    = $app->getUserStateFromRequest($ns.'id_from', 'filter_id_from', '', '');
        $state['filter_id_to']      = $app->getUserStateFromRequest($ns.'id_to', 'filter_id_to', '', '');
        $state['filter_name']       = $app->getUserStateFromRequest($ns.'name', 'filter_name', '', '');
        $state['filter_enabled']    = $app->getUserStateFromRequest($ns.'enabled', 'filter_enabled', '', '');
        $state['filter_quantity_from']  = $app->getUserStateFromRequest($ns.'quantity_from', 'filter_quantity_from', '', '');
        $state['filter_quantity_to']        = $app->getUserStateFromRequest($ns.'quantity_to', 'filter_quantity_to', '', '');
        $state['filter_category']       = $app->getUserStateFromRequest($ns.'category', 'filter_category', '', '');
        $state['filter_sku']        = $app->getUserStateFromRequest($ns.'sku', 'filter_sku', '', '');
        $state['filter_price_from']     = $app->getUserStateFromRequest($ns.'price_from', 'filter_price_from', '', '');
        $state['filter_price_to']       = $app->getUserStateFromRequest($ns.'price_to', 'filter_price_to', '', '');
        $state['filter_taxclass']   = $app->getUserStateFromRequest($ns.'taxclass', 'filter_taxclass', '', '');
        $state['filter_ships']   = $app->getUserStateFromRequest($ns.'ships', 'filter_ships', '', '');
        
        foreach (@$state as $key=>$value)
        {
            $model->setState( $key, $value );   
        }
        $rowset = $model->getList();
            
        $found = false;
        $prev_id = '';
        $next_id = '';

        for ($i=0; $i < count($rowset) && empty($found); $i++) 
        {
            $row = $rowset[$i];     
            if ($row->product_id == $id) 
            { 
                $found = true; 
                $prev_num = $i - 1;
                $next_num = $i + 1;
                if (isset($rowset[$prev_num]->product_id)) { $prev_id = $rowset[$prev_num]->product_id; }
                if (isset($rowset[$next_num]->product_id)) { $next_id = $rowset[$next_num]->product_id; }
    
            }
        }
        
        $return["prev"] = $prev_id;
        $return["next"] = $next_id; 
        return $return;
    }
    
    /**
     * Given a multi-dimensional array, 
     * this will find all possible combinations of the array's elements
     *
     * Given:
     * 
     * $traits = array
     * (
     *   array('Happy', 'Sad', 'Angry', 'Hopeful'),
     *   array('Outgoing', 'Introverted'),
     *   array('Tall', 'Short', 'Medium'),
     *   array('Handsome', 'Plain', 'Ugly')
     * );
     * 
     * Returns:
     * 
     * Array
     * (
     *      [0] => Happy,Outgoing,Tall,Handsome
     *      [1] => Happy,Outgoing,Tall,Plain
     *      [2] => Happy,Outgoing,Tall,Ugly
     *      [3] => Happy,Outgoing,Short,Handsome
     *      [4] => Happy,Outgoing,Short,Plain
     *      [5] => Happy,Outgoing,Short,Ugly
     *      etc
     * )
     * 
     * @param string $string   The result string
     * @param array $traits    The multi-dimensional array of values
     * @param int $i           The current level
     * @param array $return    The final results stored here
     * @return array           An Array of CSVs
     */
    function getCombinations($string, $traits, $i, &$return)
    {
        if ($i >= count($traits))
        {
            $return[] = str_replace(' ', ',', trim($string)); 
        }
            else
        {
            foreach ($traits[$i] as $trait)
            {
                TiendaHelperProduct::getCombinations("$string $trait", $traits, $i + 1, $return);
            }
        }
    }
    
    /**
     * Will return all the CSV combinations possible from a product's attribute options
     * 
     * @param unknown_type $product_id
     * @return unknown_type
     */
    function getProductAttributeCSVs( $product_id )
    {
        $return = array();
        $traits = array();
        
        JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
        
        // get all productattributes
        $model = JModel::getInstance('ProductAttributes', 'TiendaModel');
        $model->setState('filter_product', $product_id);
        if ($attributes = $model->getList())
        {
            foreach ($attributes as $attribute)
            {
                $paoModel = JModel::getInstance('ProductAttributeOptions', 'TiendaModel');
                $paoModel->setState('filter_attribute', $attribute->productattribute_id);
                if ($paos = $paoModel->getList())
                {
                    $options = array();
                    foreach ($paos as $pao)
                    {
                        $options[] = $pao->productattributeoption_id;
                    }
                    $traits[] = $options;
                }
            }
        }
        
        // run recursive function on the data
        TiendaHelperProduct::getCombinations( "", $traits, 0, $return );
        
        // before returning them, loop through each record and sort them
        $result = array();
        foreach ($return as $csv)
        {
            $values = explode( ',', $csv );
            sort($values);
            $result[] = implode(',', $values);
        }

        return $result;
    }

    /**
     * Given a product_id and vendor_id
     * will perform a full CSV reconciliation of the _productquantities table
     * 
     * @param $product_id
     * @param $vendor_id
     * @return unknown_type
     */
    function doProductQuantitiesReconciliation( $product_id, $vendor_id='0' )
    {
        $csvs = TiendaHelperProduct::getProductAttributeCSVs( $product_id );

        JModel::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'models' );
        $model = JModel::getInstance('ProductQuantities', 'TiendaModel');
        $model->setState('filter_productid', $model->getId());
        $model->setState('filter_vendorid', '0');
        $items = $model->getList();
        
        $results = TiendaHelperProduct::reconcileProductAttributeCSVs( $product_id, $vendor_id, $items, $csvs );
    }
    
    /**
     * Adds any necessary _productsquantities records 
     * 
     * @param unknown_type $product_id     Product ID
     * @param unknown_type $vendor_id      Vendor ID
     * @param array $items                 Array of productQuantities objects
     * @param unknown_type $csvs           CSV output from getProductAttributeCSVs
     * @return array $items                Array of objects
     */
    function reconcileProductAttributeCSVs( $product_id, $vendor_id, $items, $csvs )
    {
        if (count($items) == count($csvs))
        {
            return $items;
        }

        // remove extras
        foreach ($items as $key=>$item)
        {
            if (!in_array($item->product_attributes, $csvs))
            {
                $row = JTable::getInstance('ProductQuantities', 'TiendaTable');
                if (!$row->delete($item->productquantity_id))
                {
                    JError::raiseNotice('1', $row->getError());
                }
                unset($items[$key]);
            }
        }
        
        // add new ones
        $existingEntries = TiendaHelperBase::getColumn( $items, 'product_attributes' );
        JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_tienda'.DS.'tables' );
        foreach ($csvs as $csv)
        {
            if (!in_array($csv, $existingEntries))
            {
                $row = JTable::getInstance('ProductQuantities', 'TiendaTable');
                $row->product_id = $product_id;
                $row->vendor_id = $vendor_id;
                $row->product_attributes = $csv;
                if (!$row->save())
                {
                    JError::raiseNotice('1', $row->getError());
                }
                $items[] = $row; 
            }
        }
        
        return $items;
    }
}