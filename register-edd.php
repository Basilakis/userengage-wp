<?php
/*
Plugin Name: UserEngage for Helpscout
Plugin URI:
Description:
Author: Basilis Kanonidis
Author URI:http://creativeg.gr
Version: 1.1.2
License: GPLv2 or later
 */
require 'Userengage.php';

define('API_KEY', 'QB2gESzIXGJAHNw8PVPRXEAf0lujKGdbXgqjXTbI76Q2VOfO44ehbj2ShzKgvlgN');

add_filter('cron_schedules', 'isa_add_every_ten_minutes');
function isa_add_every_ten_minutes($schedules)
{
    $schedules['ten_minutes'] = array(
        'interval' => 10,
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

    $users = get_users();

    if (!empty($users)) {
        foreach ($users as $user) {
            $userExists    = findUserByEmail($user->user_email);
            $userInfo      = get_userdata($user->ID);
            $subscriber    = new EDD_Recurring_Subscriber($user->ID, true);
            $subscriptions = $subscriber->get_subscriptions(0, array('active', 'expired', 'cancelled', 'failing', 'trialling'));

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

            // addToRegisteredList($userInfo->user_email); //adds if not already registered

            // EDD Product Validation
            $payments = edd_get_users_purchases($user->ID, 20, true, 'any');
            if ($payments):
                foreach ($payments as $payment):
                    try
                    {
                        $ue = new UserEngage(API_KEY);
                        $ue->setEndpoint('users/' . $userExists->id . '/set_multiple_attributes');
                        $ue->setMethod('POST');
                        // Add the required fields:
                        if ($subscriptions):
                            foreach ($subscriptions as $subscription):
                                $frequency    = EDD_Recurring()->get_pretty_subscription_frequency($subscription->period);
                                $renewal_date = !empty($subscription->expiration) ? date_i18n(get_option('date_format'), strtotime($subscription->expiration)) : 'N/A';
                                $ue->addField('subscription', $subscription->get_status_label( $user->ID ));
                                $ue->addField('subscription_expires', $renewal_date );
                                if (strtolower($subscription->get_status($user->ID)) == 'active') {
                                    //adding usr to active users
                                    addToActiveRegisteredList($userInfo->user_email);
                                }

                                if (strtolower($subscription->get_status($user->ID)) == 'expired') {
                                    addToUnRegisteredList($userInfo->user_email);
                                }
                        
                                if (strtolower($subscription->get_status($user->ID)) == 'cancelled') {
                                    addToCancelRegisteredList($userInfo->user_email);
                                }
                            endforeach;
                        endif;
                        $ue->addField('edd_total', cg_edd_get_total_spent_for_customer( $user->ID ) );
                        $ue->addField('edd_purchases', edd_count_purchases_of_customer( $user->ID ) );
                        // print_r( $ue->send() );
                        $ue->send();

                    } catch (Exception $e) {
                        // echo $e->getMessage();
                    }

                    addToProductList($userInfo->user_email);
                    addToGeneralList($userInfo->user_email);

                endforeach;
            endif;

        }
    }
}

/*
 * Expired List
 */
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

function addToProductList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 2782); //this list is not yet created
        $ue->send();
    } catch (Exception $e) {
        // echo $e->getMessage();
    }
}
function addToGeneralList($email)
{
    $exist  = findUserByEmail($email);
    $userId = $exist->id;
    //now adding to unregisteredlist
    try
    {
        $ue = new UserEngage(API_KEY);
        $ue->setEndpoint('users/' . $userId . '/add_to_list');
        $ue->setMethod('POST');
        $ue->addField('list', 1142); //this list is not yet created
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
