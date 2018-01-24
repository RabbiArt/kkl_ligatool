<?php
/**
 * Created by IntelliJ IDEA.
 * User: stephan
 * Date: 20.01.18
 * Time: 16:53
 */

class KKL_Events_Service {

  public static $MATCH_FIXTURE_SET = 'kkl_match_fixture_has_been_set';

  public static function fireEvent($name, KKL_Event $event) {
    do_action($name, $event);
  }

  public static function registerCallback($eventName, $function) {
    add_action($eventName, $function, 10, 1);
  }

}