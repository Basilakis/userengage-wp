<?php
/*
Plugin Name: Register
Plugin URI:
Description:
Author: Rajiv Shakya
Author URI:http://jyasha.com
Version: 1.1.2
License: GPLv2 or later
 */
require 'Userengage.php';

define('API_KEY', '');

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
function register_schedule() {
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

            // EDD Product Validation
            $productArray = array(7825,4915,2906,2760,2176);
            foreach ($productArray as $productId) {
                if (edd_has_user_purchased($user->ID, $productId)) {
                    try
                    {
                        $ue = new UserEngage(API_KEY);
                        $ue->setEndpoint('users/' . $userExists->id . '/set_multiple_attributes');
                        $ue->setMethod('POST');
                        // Add the required fields:
                        $ue->addField('subscription', EDD_Recurring_Customer::get_customer_status( $user->ID ));
                        $ue->addField('subscription_expires', date_i18n( get_option( 'date_format' ), EDD_Recurring_Customer::get_customer_expiration( $user->ID )));
                        $ue->addField('edd_total', cg_edd_get_total_spent_for_customer( $user->ID ));
                        $ue->addField('edd_purchases', edd_count_purchases_of_customer( $user->ID ));
                        // print_r( $ue->send() );
                        $ue->send();

                    } catch (Exception $e) {
                        // echo $e->getMessage();
                    }
                    
                    addToProductList($userInfo->user_email);
                    
                    // Subscription Lists
                    $subscription = EDD_Recurring_Customer::get_customer_status( $user->ID );
                    $subscription_status_userengage = $userExists->attributes[0]->value;
                    if (strtolower($subscription) == 'active') {
                    //adding usr to registered list
                        addToActiveRegisteredList($userInfo->user_email);
                            }
                    if (strtolower($subscription) == 'expired') {
                        addToUnRegisteredList($userInfo->user_email);
                            }
                    }
                }
        }
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
        $ue->addField('list', 2294);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
    
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1141);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
    
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1841);
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
        $ue->addField('list', 1144); //this list is not yet created
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
        $ue->addField('list', 2294);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
    
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1144);
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
    
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1841);
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
        $ue->addField('list', 1141); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToCancelRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/remove_from_list');
        $ue->setMethod('POST');
        // Add the required fields:
        $ue->addField('list', 1141);
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
        $ue->addField('list', 1841); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToPendingRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 2294); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToFailedRegisteredList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 2295); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToProductList($id)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 1145); //this list is not yet created
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