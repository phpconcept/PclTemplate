<?php
require_once('../pcltrace.lib.php');
PcltraceOn(5);
require_once('../pcltemplate-trace.class.php');

  
// ----- Create the template object
$v_template = new PclTemplate();

// ----- Parse the template file
$v_template->parseFile('model-global-token.htm');

// ----- Prepare data
$v_att = array();

// ----- Set the values of the simple tokens
$v_att['page_name'] = 'First Generated Page';
$v_att['user_name'] = 'Vincent Blavet';

// ----- Set the values of the list tokens
$v_att['users'][0]['notreverse']['first_name'] = 'Vincent';
$v_att['users'][0]['notreverse']['last_name'] = 'Blavet';
$v_att['users'][1]['reverse']['first_name'] = 'Pierre';
$v_att['users'][1]['reverse']['last_name'] = 'Martin';

// ----- Set the values of the list tokens
$v_att['do_list']['table_name'] = 'Users 2';
$v_att['do_list']['users2'][0]['first_name'] = 'Vincent';
$v_att['do_list']['users2'][0]['last_name'] = 'Blavet';
$v_att['do_list']['users2'][1]['first_name'] = 'Pierre';
$v_att['do_list']['users2'][1]['last_name'] = 'Martin';

// ----- Prepare data
$v_att_globals = array();

// ----- Set the values of the simple token condition
$v_att_globals['name'] = 'Georges';

// ----- Set global values
$v_template->setGlobals($v_att_globals);

// ----- Generate result in a string
$v_result = $v_template->generate($v_att, 'string');

// ----- Display result
echo $v_result;

PclTraceDisplayNew();
?>
