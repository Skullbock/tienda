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

Tienda::load( 'TiendaModelBase', 'models._base' );

class TiendaModelDashboard extends TiendaModelBase
{
    /**
     * @var string a CSV of order_state_ids that you want dashboard to report on
     * default: '2','3','5','17' = cash in hand
     */
    var $_statesCSV = null;
    
    public function getTable($name='Config', $prefix='TiendaTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getStatIntervalValues($stats_interval)
    {
        $this->setStatesCSV();
        
        $interval = new stdClass();

        switch ($stats_interval) {
            case "annually":
                $firstsale_date = TiendaHelperOrder::getDateMarginalOrder( $this->getStatesCSV(), 'ASC' );
                
                $interval->date_from = date( 'Y-01-01 00:00:00', strtotime( $firstsale_date ) );
                $interval->date_to = 'NOW';
                $interval->next_date = '+1 year';
                $interval->step = '1';
                $interval->pointinterval = 365 * 24 * 3600 * 1000; // one year
                $interval->index_format = 'Y-01-01';
                $interval->current_date_format = 'Y-01-01';
                $interval->end_date_format = 'Y-12-31';
                $interval->hour_format = '0';
                break;
            case "today":
                $interval->date_from = date( 'Y-m-d 00:00:00', strtotime( 'NOW' ) );
                $interval->date_to = date( 'Y-m-d 23:59:59', strtotime( 'NOW' ) );
                $interval->next_date = '+1 hour';
                $interval->step = 1;
                $interval->pointinterval = 3600 * 1000; // one hour
                $interval->index_format = 'Y-m-d H:00:00';
                $interval->current_date_format = 'Y-m-d H:00:00';
                $interval->end_date_format = 'Y-m-d 23:59:59';
                $interval->hour_format = 'H';
                break;
            case "yesterday":
                $interval->date_from = date( 'Y-m-d 00:00:00', strtotime( 'yesterday' ) );
                $interval->date_to = date( 'Y-m-d 23:59:59', strtotime( 'yesterday' ) );
                $interval->next_date = '+1 hour';
                $interval->step = 1;
                $interval->pointinterval = 3600 * 1000; // one hour
                $interval->index_format = 'Y-m-d H:00:00';
                $interval->current_date_format = 'Y-m-d H:00:00';
                $interval->end_date_format = 'Y-m-d 23:59:59';
                $interval->hour_format = 'H';
                break;
            case "ytd":
                $interval->date_from = date( 'Y', strtotime( 'NOW' ) ) . '-01-01';
                $interval->date_to = 'NOW';
                $interval->next_date = '+1 month';
                $interval->step = 1;
                $interval->pointinterval = 30.25 * 24 * 3600 * 1000; // one month
                $interval->index_format = 'Y-m-01';
                $interval->current_date_format = 'Y-m-d';
                $interval->end_date_format = 'Y-m-d';
                $interval->hour_format = '0';
                break;
            case "last_seven":
                $interval->date_from = 'NOW -7 days';
                $interval->date_to = 'NOW';
                $interval->next_date = '+1 day';
                $interval->step = '1';
                $interval->pointinterval = 24 * 3600 * 1000; // one day
                $interval->index_format = 'Y-m-d';
                $interval->current_date_format = 'Y-m-d';
                $interval->end_date_format = 'Y-m-d';
                $interval->hour_format = '0';
                break;
            case "last_thirty":
            default:
                $interval->date_from = 'NOW -30 days';
                $interval->date_to = 'NOW';
                $interval->next_date = '+1 day';
                $interval->step = '7';
                $interval->pointinterval = 24 * 3600 * 1000; // one day
                $interval->index_format = 'Y-m-d';
                $interval->current_date_format = 'Y-m-d';
                $interval->end_date_format = 'Y-m-d';
                $interval->hour_format = '0';
                break;
        }
         
        return $interval;
    }

    public function getOrdersChartData($stats_interval)
    {
        $interval = $this->getStatIntervalValues($stats_interval);
         
        $model = JModel::getInstance( 'Orders', 'TiendaModel' );
        $datetime_field = 'created_date';
         
        $filter_date_from = date( 'Y-m-d 00:00:00', strtotime( $interval->date_from ) );
        $filter_date_to = date( 'Y-m-d 23:59:59', strtotime( $interval->date_to ) );

        $model->setState( 'filter_date_from', $filter_date_from );
        $model->setState( 'filter_date_to', $filter_date_to );
         
        $ordersQuery = $model->getQuery();
        $ordersQuery->where("tbl.order_state_id IN (".$this->getStatesCSV().")");
        $model->setQuery($ordersQuery);

        $days = array();
        $current_date = date($interval->current_date_format, strtotime( $filter_date_from ) );
        $end_date = date($interval->end_date_format, strtotime( $filter_date_to ) );
         
        while ($current_date <= $end_date)
        {
            if (empty($days[$current_date]))
            {
                $days[$current_date] = 0;
            }
            $current_date = date($interval->current_date_format, strtotime( $current_date . " " . $interval->next_date ) );
        }

        if ($items = $model->getList( true ))
        {
            foreach ($items as $item)
            {
                $date = date($interval->index_format, strtotime( $item->$datetime_field ) );
                $days[$date]++;
            }
        }

        $this->orders = 0;
        $return = array();
        foreach ($days as $key=>$value)
        {
            $year = date( 'Y', strtotime($key) );
            $month = date( 'n', strtotime($key) );
            $day = date( 'j', strtotime($key) );
            $hour = date( $interval->hour_format, strtotime($key) );
            $minute = date( '0', strtotime($key) );
            $second = date( '0', strtotime($key) );
            
            // gmmktime(0,0,0,$month,$day,$year)*1000 = javascript's Date.UTC (milliseconds since Epoch)
            $return[] = array( gmmktime($hour,$minute,$second,$month,$day,$year)*1000, $value );
            $this->orders += $value;
        }

        return $return;
    }

    public function getRevenueChartData($stats_interval)
    {
        $interval = $this->getStatIntervalValues($stats_interval);
         
        $model = JModel::getInstance( 'Orders', 'TiendaModel' );
        $datetime_field = 'created_date';

        $filter_date_from = date( 'Y-m-d 00:00:00', strtotime( $interval->date_from ) );
        $filter_date_to = date( 'Y-m-d 23:59:59', strtotime( $interval->date_to ) );

        $model->setState( 'filter_date_from', $filter_date_from );
        $model->setState( 'filter_date_to', $filter_date_to );
         
        $ordersQuery = $model->getQuery();
        $ordersQuery->where("tbl.order_state_id IN (".$this->getStatesCSV().")");
        $model->setQuery($ordersQuery);

        $days = array();
        $current_date = date($interval->current_date_format, strtotime( $filter_date_from ) );
        $end_date = date($interval->end_date_format, strtotime( $filter_date_to ) );

        while ($current_date <= $end_date)
        {
            if (empty($days[$current_date]))
            {
                $days[$current_date] = 0;
            }
            $current_date = date($interval->current_date_format, strtotime( $current_date . " " . $interval->next_date ) );
        }

        if ($items = $model->getList( true ))
        {
            foreach ($items as $item)
            {
                $date = date($interval->index_format, strtotime( $item->$datetime_field ) );
                $days[$date] += $item->order_total;
            }
        }

        $this->revenue = 0;
        $return = array();
        foreach ($days as $key=>$value)
        {
            $year = date( 'Y', strtotime($key) );
            $month = date( 'n', strtotime($key) );
            $day = date( 'j', strtotime($key) );
            $hour = date( $interval->hour_format, strtotime($key) );
            $minute = date( '0', strtotime($key) );
            $second = date( '0', strtotime($key) );
            
            // gmmktime(0,0,0,$month,$day,$year)*1000 = javascript's Date.UTC (milliseconds since Epoch)
            $return[] = array( gmmktime($hour,$minute,$second,$month,$day,$year)*1000, $value );
            $this->revenue += $value;
        }

        return $return;
    }

    /**
     * Set the CSV of states to be reported on
     * @param $csv
     * @return unknown_type
     */
    function setStatesCSV( $csv='' )
    {
        if (empty($csv))
        {
            $csv = Tienda::getInstance()->get('orderstates_csv', '2, 3, 5, 17');
        }

        $array = explode(',', $csv);
        $this->_statesCSV = "'".implode("','", $array)."'";
    }

    /**
     * Get the CSV of states to be reported on
     * @return unknown_type
     */
    function getStatesCSV()
    {
        if (empty($this->_statesCSV))
        {
            $this->setStatesCSV();
        }

        return $this->_statesCSV;
    }
}