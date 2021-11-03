<?php
require_once('../pcltrace.lib.php');
PcltraceOn(5);
require_once('../pcltemplate-trace.class.php');

  
// ----- Create the template object
$v_template = new PclTemplate();

// ----- Parse the template file
$v_template->parseFile('model-system-token.htm');

// ----- Prepare data
$v_att = array();

// ----- Set the values of the simple tokens
$v_att['page_name'] = 'First Generated Page';
$v_att['user_name'] = 'Vincent Blavet';

// ----- Set the values of the list tokens
$v_att['do_list']['table_name'] = 'Users 2';

// ----- Generate result in a string
$v_result = $v_template->generate($v_att, 'string');

// ----- Display result
echo $v_result;

PclTraceDisplayNew();
?>
