<?php
/*
Plugin Name: UserEngage for WordPress & RCP
Plugin URI: http://creativeg.gr
Description: Connect your WordPress Installation With WordPress & Restrict Content Pro
Author: creativeG
Author URI:http://creativeg.gr
Version: 1.1.1
License: GPLv3 or later
 */
require 'Userengage.php';
define('API_KEY', ''); // Your API KEY
add_filter('cron_schedules', 'isa_add_every_ten_minutes');
function isa_add_every_ten_minutes($schedules)
{
    $schedules['ten_minutes'] = array(
        'interval' => 600,
        'display'  => __('Every 10 Minutes', 'textdomain'),
    );
    return $schedules;
}
if (!wp_next_scheduled('my_ten_min_event')) {
    wp_schedule_event(time(), 'ten_minutes', 'my_ten_min_event');
}
add_action('my_ten_min_event', 'register_schedule');
// add_action('plugins_loaded', 'register_schedule');
function register_schedule()
{
    // $members = rcp_get_members();
    $users = get_users();
    if (!empty($users)) {
        foreach ($users as $user) {
            $userExists = findUserByEmail($user->user_email);
            $userInfo   = get_userdata($user->ID);
            //getting from rcp
           
            if (!$userExists) {
                try
                {
                    $ue = new UserEngage(API_KEY);
                    $ue->setEndpoint('users');
                    $ue->setMethod('POST');
                    // Add the required fields:
                    $ue->addField('email', $userInfo->user_email);
                    $ue->addField('first_name', $userInfo->first_name);
                    $ue->addField('last_name', $userInfo->last_name);
                    $ue->send();
                } catch (Exception $e) {
                    // echo $e->getMessage();
                }
                $userExists       = findUserByEmail($user->user_email);
                $userEngageUserId = $userExists->id;

            } else {
                $userEngageUserId = $userExists->id;
            }
        	$member = rcp_get_members( 'active', '', '', '', '', '', $user->user_email);
        	if ($member) {
        		$member = $member[0];
        	} else {
        		$member = rcp_get_members( 'expired', '', '', '', '', '', $user->user_email);
        		$member = $member[0];
        	}
            
            if ($member) {
            	$subscription = rcp_get_status($member->data->ID);	
                $subscription_status_userengage = $userExists->attributes[0]->value;
                if (strtolower($subscription) == 'active') {
                    //adding usr to registered list
                    addToActiveRegisteredList($userInfo->user_email);
                }

                if (strtolower($subscription) == 'expired') {
                    addToUnRegisteredList($userInfo->user_email);
                }

                
                if (strtolower($subscription) != strtolower($subscription_status_userengage)) {
                    //adding attributes
                    try
                    {
                        $ue = new UserEngage(API_KEY);
                        $ue->setEndpoint('users/' . $userExists->id . '/set_multiple_attributes');
                        $ue->setMethod('POST');
                        // Add the required fields:
                        $ue->addField('subscription', rcp_get_status($member->data->ID));
                        $ue->addField('subscription_expires', date('F d, Y', strtotime(rcp_get_expiration_date($member->data->ID))));
                        // print_r( $ue->send() );
                        $ue->send();

                    } catch (Exception $e) {
                        // echo $e->getMessage();
                    }
                }

            }else{
                addToRegisteredList($userInfo->user_email);
            }

            // EDD Product Validation
            $productArray = array(4915, 2906, 2760, 2176);
            foreach ($productArray as $productId) {
                if (edd_has_user_purchased($user->ID, $productId)) {
                    addToProductList($userEngageUserId);
                }
            }
        }

    }
}
// echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';
function addToRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1314);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToUnRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1312);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 1247); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToActiveRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1247);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }

    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 1312); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToProductList($id)
{
    $userId = $id;
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1315);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function findUserByEmail($email)
{
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/search/?email=' . $email);
        $ue->setMethod('GET');
        $result = $ue->send();
        return $result;
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
