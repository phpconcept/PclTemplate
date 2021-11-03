<?php
// -----------------------------------------------------------------------------
// PhpConcept Template Engine - pcltemplate.class.php
// -----------------------------------------------------------------------------
// License GNU/LGPL - Vincent Blavet - November 2006
// http://www.phpconcept.net
// -----------------------------------------------------------------------------
// Overview :
//   See http://www.phpconcept.net/pcltemplate for more information
// -----------------------------------------------------------------------------
  
  // ----- Global Constants
  define('PCL_TEMPLATE_VERSION', '0.6');
  if (!defined('PCL_TEMPLATE_START')) define('PCL_TEMPLATE_START', '<!--(');
  if (!defined('PCL_TEMPLATE_STOP')) define('PCL_TEMPLATE_STOP', ')-->');
  if (!defined('PCL_DOCUMENT_ROOT')) {
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
      define('PCL_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
    }
    else {
      define('PCL_DOCUMENT_ROOT', '');
    }
  }
  
  // ----- Error codes
  define( 'PCL_TEMPLATE_ERR_NO_ERROR', 1 );
  define( 'PCL_TEMPLATE_ERR_GENERIC', 0 );
  define( 'PCL_TEMPLATE_ERR_SYNTAX', -1 );
  define( 'PCL_TEMPLATE_ERR_READ_OPEN_FAIL', -2 );
  define( 'PCL_TEMPLATE_ERR_WRITE_OPEN_FAIL', -3 );
  define( 'PCL_TEMPLATE_ERR_INVALID_PARAMETER', -4 );

  // ---------------------------------------------------------------------------
  // Class : PclTemplate
  // Description :
  // Attributes :
  // Methods :
  // ---------------------------------------------------------------------------
  class PclTemplate
  {
    // ----- $template_name
    // Filename of the template. When the template is not a string.
    var $template_name;
    
    // ----- $token_start & $token_stop
    // The tokens delimiters. They have default values. But can be changed
    // dynamically.
    var $token_start;
    var $token_stop;
    
    // ----- $tokens
    // The recursive array that contain the template in memory after parsing
    // The format of this array is :
    // tokens[]['type'] : Type of the token. The valid types are :
    //                    'line' : A text beetwen 2 tokens
    //                    'token' : A single reference to remplace by the real 
    //                              value.
    //                    'list' : A part of the template that will be 
    //                             associated to an array
    //                    'item' : A part of the template that will be repeated
    //                             for each element of an array
    //                    'ifempty' : A part of the template, associated to an
    //                                array that will be used if the array is
    //                                empty.
    //                    'ifnotempty' : A part of the template, associated to 
    //                                   an array that will be used if the
    //                                   array is not empty.
    //                    'if' : A part of the template that will be used if a
    //                           condition matches.
    //                    'ifnot' : A part of the template that will be used if
    //                              a condition does not match.
    // tokens[]['name'] : The name of the token to identify it. The same name
    //                    will be used in that structure that will fill the 
    //                    template.
    // tokens[]['text'] : The text that follow the token (same as 'line').    
    // tokens[]['tokens'] : Array of childs tokens
    var $tokens;
    
    // ----- System values
    // System values are globally available tokens that contains system
    // affected values.
    // When PclTemplate is called some reserved values are automatically
    // provided to the user.
    // These values are :
    //   system_values['filename'] : filename of the template (without path)
    //   system_values['filepath'] : path to the template file relative to 
    //                               filesystem
    //   system_values['path_from_root'] : The path from the web server root if
    //                                     any. i.e. path relative to 
    //                                     $_SERVER['DOCUMENT_ROOT']
    //   system_values['path_document_root'] : The path of the web server root 
    //                                         if any i.e. PCL_DOCUMENT_ROOT
    //   system_values['date'] : Date when the file is generated format is
    //                           YYYY/MM/DD hh:mm:ss
    var $system_values;
    
    // ----- Global values
    // Global values are user provided values that are available everywhere
    // in the template hierarchie. Global values are the same for any included
    // template files.
    var $global_values;
    
    // ----- Internal error handling
    // error_list[]['code'] : The error code.
    // error_list[]['text'] : The associated error string.
    // error_list[]['date'] : The associated error time and date.
    var $error_list;
    
    // ----- Working data
    // This array will contain the array of all values send by the user.
    // working_data['user_data'] : Data structure give by the user.
    var $working_data;
    
    // -------------------------------------------------------------------------
    // Function : PclTemplate()
    // Description :
    // -------------------------------------------------------------------------
    function PclTemplate()
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::PclTemplate', '');

      $this->template_name = '';
      $this->tokens = array();
      $this->token_start = PCL_TEMPLATE_START;
      $this->token_stop = PCL_TEMPLATE_STOP;
      
      $this->error_list = array();
      
      // ----- Set default system_values
      $this->system_values['filename'] = '';
      $this->system_values['filepath'] = '';
      $this->system_values['path_from_root'] = '';
      $this->system_values['date'] = '';
      
      // ----- Set global values
      $this->global_values = array();
      
      // ----- Set default working data structure
      $this->working_data = array();
      $this->working_data['user_data'] = array();
      
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, 1);
      return;
    }
    // -------------------------------------------------------------------------
  
    // -------------------------------------------------------------------------
    // Function : errorInfo()
    // Description :
    // -------------------------------------------------------------------------
    function errorInfo()
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::errorInfo', '');
      
      $v_text = '';
      
      if (!isset($this->error_list) || (!is_array($this->error_list))) {
        $v_text = $this->_error_name(PCL_TEMPLATE_ERR_NO_ERROR)."(".PCL_TEMPLATE_ERR_NO_ERROR.")"; 
      }
      else {
        foreach ($this->error_list as $v_error) {
          $v_text .= $this->_error_name($v_error['code'])."(".$v_error['code'].") : ".$v_error['text']." (".$v_error['date'].")\n"; 
        }
      }
      $v_text = trim($v_text);

      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_text);
      return($v_text);
    }
    // -------------------------------------------------------------------------
  
    // -------------------------------------------------------------------------
    // Function : changeDelimiters()
    // Description :
    //   Change the delimiters strings used in the template file.
    //   No coherency check is done with the used string. The user
    //   must be sure that the delimiters are coherent.
    // -------------------------------------------------------------------------
    function changeDelimiters($p_start_delimiter, $p_stop_delimiter)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::changeDelimiters', 'start="'.$p_start_delimiter.'", stop="'.$p_stop_delimiter.'"');
      
      $this->token_start = $p_start_delimiter;
      $this->token_stop = $p_stop_delimiter;

      $v_result=1;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
  
    // -------------------------------------------------------------------------
    // Function : setGlobals()
    // Description :
    //   Change the delimiters strings used in the template file.
    //   No coherency check is done with the used string. The user
    //   must be sure that the delimiters are coherent.
    // -------------------------------------------------------------------------
    function setGlobals($p_values)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::setGlobals', '');
      
      // TBC : Should check that this is a valid array
      $this->global_values = $p_values;

      $v_result=1;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Function : parseFile()
    // Description :
    // Return values :
    //   PCL_TEMPLATE_ERR_NO_ERROR : on success.
    //   PCL_TEMPLATE_ERR_READ_OPEN_FAIL : unable to open the template file in 
    //                                     read mode.
    //   PCL_TEMPLATE_ERR_SYNTAX : template syntax error.
    //   PCL_TEMPLATE_ERR_GENERIC : other errors.
    // -------------------------------------------------------------------------
    function parseFile($p_template)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::parseFile', 'template="'.$p_template.'"');
      $v_result = 1;
      
      // ----- Reset logs
      $this->_error_reset();
      
      // ----- Store the current template filename
      $this->template_name = realpath($p_template);
      
      // ----- Try to open the template file in read mode
      $handle = @fopen($this->template_name, "r");
      if (!$handle) {
        $v_result = 0;
        //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result, "Unable to open '".$this->template_name."'");
        return($v_result);
      }

      // ----- Start the template reading with an empty array of tokens.
      // Call the recursive parsing method to fill the list of tokens or
      // blocks.
      $v_buffer = '';
      $v_start_token = array();
      $v_line_number = 0;
      $v_result = $this->_parse_recursive($v_start_token, $v_buffer, $v_line_number, $handle);
      @fclose($handle);
      if ($v_result != 1) {
        //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
        return($v_result);
      }
      
      // ----- Store the list in the PclTemplate object
      $this->tokens = $v_start_token['tokens'];
      
      $v_result=1;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
  
    // -------------------------------------------------------------------------
    // Function : parseString()
    // Description :
    // -------------------------------------------------------------------------
    function parseString($p_string)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::parseString', '');
      $v_result = 1;
      
      // ----- Reset logs
      $this->_error_reset();

      $v_start_token = array();
      $v_line_number = 0;
      $v_result = $this->_parse_recursive($v_start_token, $p_string, $v_line_number);
      if ($v_result != 1) {
        //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
        return($v_result);
      }
      
      $this->tokens = $v_start_token['tokens'];
      
      $v_result=1;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
  
    // -------------------------------------------------------------------------
    // Function : _parse_recursive()
    // Description :
    // Return values :
    //   PCL_TEMPLATE_ERR_NO_ERROR : on success.
    //   PCL_TEMPLATE_ERR_SYNTAX : template syntax error.
    //   PCL_TEMPLATE_ERR_GENERIC : other errors.
    // -------------------------------------------------------------------------
    function _parse_recursive(&$p_token, &$p_buffer, &$p_line, $p_fd=0)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_parse_recursive', '');
      $v_result = 1;
      
      // ----- Initialize
      if (isset($p_token['tokens']) && is_array($p_token['tokens'])) {
        $v_token_list = $p_token['tokens'];
        $v_current_index = sizeof($v_token_list);
      }
      else {
        $v_token_list = array();
        $v_current_index = 0;
      }
      
      $v_token_list[$v_current_index]['type'] = 'line';
      $v_token_list[$v_current_index]['text'] = '';
      
      do {
        // ----- Look if no token delimiter in the current line
        // which means the line is a single text and we add it
        // in the current text token
        if (($v_pos = strpos($p_buffer,$this->token_start)) === FALSE) {
          //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Line has no token");
          $v_token_list[$v_current_index]['text']  .= $p_buffer; 
          $p_buffer = '';          
        }
        
        // ----- The buffer has a start delimiter
        else {
          //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Token found at '".$v_pos."'");
          $v_token_list[$v_current_index]['text']  .= substr($p_buffer, 0, $v_pos);
          $v_pos += strlen($this->token_start);
          $p_buffer = substr($p_buffer, $v_pos);
          ////--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Start token : '".htmlentities($p_buffer)."'");
          $v_pos2 = strpos($p_buffer, $this->token_stop);
          ////--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Line : '".htmlentities($p_buffer)."' end at ".$v_pos2);
          //$v_token = strtolower(substr($p_buffer, $v_pos, $v_pos2-$v_pos));     
          $v_token = strtolower(substr($p_buffer, 0, $v_pos2));     
          $v_pos2 += strlen($this->token_stop);
          //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "token is '".htmlentities($v_token)."'");
          $p_buffer = substr($p_buffer, $v_pos2);
          
          // ----- Parse the token structure & Create the new token
          $v_current_index++;
          if (strpos($v_token, ":") === FALSE) {
            // ----- Look if $v_token is a reserved keyword
            // If not then by default the type is 'token',
            // If yes, then it is a token with no name
            if ($this->_is_keyword($v_token)) {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "token is reserved keyword");
              $v_token_list[$v_current_index]['type'] = $v_token;
              $v_token_list[$v_current_index]['name'] = '';
              $v_tok = $v_token;
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "value is not a reserved keyword, type is set to 'token'");
              $v_token_list[$v_current_index]['type'] = 'token';
              $v_token_list[$v_current_index]['name'] = $v_token;
              $v_tok = 'token';
            }
          }
          else {
            // ----- Separate token type from token name
            list($v_tok,$v_name) = explode(":", $v_token);
            //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "short token is '".htmlentities($v_tok)."'");
            //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "short name is '".htmlentities($v_name)."'");
            $v_token_list[$v_current_index]['type'] = $v_tok;
            $v_token_list[$v_current_index]['name'] = $v_name;
          }
            
          switch ($v_tok) {
            case 'token' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
            break;

            case 'include' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
            break;

            case 'list.start' :
            case 'list' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              $v_token_list[$v_current_index]['type'] = 'list';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'list.stop' :
            case 'endlist' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'list')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected list parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'endlist' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current list
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            case 'list.empty.start' :
            case 'ifempty' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'list')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected list parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'ifempty' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
              $v_token_list[$v_current_index]['type'] = 'ifempty';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'list.empty.stop' :
            case 'endifempty' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'ifempty')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected empty parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'endifempty' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current list
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            case 'list.notempty.start' :
            case 'ifnotempty' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'list')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected list parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'ifnotempty' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
              $v_token_list[$v_current_index]['type'] = 'ifnotempty';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'list.notempty.stop' :
            case 'endifnotempty' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'ifnotempty')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected ifnotempty parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'endifnotempty' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current list
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            case 'list.item.start' :
            case 'item' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'list')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected list parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'item' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
              $v_token_list[$v_current_index]['type'] = 'item';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'list.item.stop' :
            case 'enditem' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'item')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected item parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'enditem' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current list
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            case 'if.start' :
            case 'if' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              $v_token_list[$v_current_index]['type'] = 'if';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'if.stop' :
            case 'endif' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'if')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected 'if' parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'endif' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current if
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            case 'ifnot.start' :
            case 'ifnot' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");          
              $v_token_list[$v_current_index]['type'] = 'ifnot';
              $v_result = $this->_parse_recursive($v_token_list[$v_current_index], $p_buffer, $p_line, $p_fd);
              if ($v_result != 1) {
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }
            break;

            case 'ifnot.stop' :
            case 'endifnot' :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Valid token type '".$v_tok."' found");
              // ----- Check that the parent token is a list with same name
              if ((!isset($p_token['type'])) || ($p_token['type'] != 'ifnot')) {
                //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Template parse error : expected 'ifnot' parent");
                $this->_error_log(PCL_TEMPLATE_ERR_SYNTAX, "Parsing error : unexpected token 'endifnot' in file '".$this->template_name."' line ".$p_line);
                $v_result=PCL_TEMPLATE_ERR_SYNTAX;
                //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
                return($v_result);      
              }

              // ----- Not a new token, but end of current if
              unset($v_token_list[$v_current_index]);
              $p_token['tokens'] = $v_token_list;                
              $v_result=1;
              //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
              return($v_result);      
            break;

            default :
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Unknown token type '".$v_tok."', replacing by token");          
              $v_token_list[$v_current_index]['type'] = 'token';
          }
        
          // TBC : can be removed ... A token other than line may not have text
          $v_token_list[$v_current_index]['text'] = '';

          $v_current_index++;
          $v_token_list[$v_current_index]['type'] = 'line';
          $v_token_list[$v_current_index]['text'] = '';

          ////--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "current line : '".htmlentities($v_current_line)."'");
        }
        
        if (($p_buffer == '') && ($p_fd != 0)) {
          $p_buffer = fgets($p_fd, 4096);
          $p_line++;
        }
        
      } while (   ($p_buffer != '') && ($p_buffer !== FALSE)
               && (($p_fd == 0) || (!feof($p_fd))));
      
      $p_token['tokens'] = $v_token_list;
      
      $v_result=1;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------  

    // -------------------------------------------------------------------------
    // Function : generate()
    // Description :
    // Arguments :
    //   $p_output : 'stdout', 'file', 'string'
    //   $p_filename : filename when 'file' is used in $p_output
    // Return Values :
    //   a string when $p_output='string' and no error
    //   PCL_TEMPLATE_ERR_NO_ERROR : If no error.
    //   0 : on error.
    // -------------------------------------------------------------------------
    function generate($p_struct, $p_output='stdout', $p_filename='')
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::generate', 'filename="'.$p_filename.'"');
      $v_result = 1;
      
      $fd = 0;
      if ($p_output == 'file') {
        if (!($fd = @fopen($p_filename, "w"))) {
          $v_result = PCL_TEMPLATE_ERR_WRITE_OPEN_FAIL;
          $this->_error_log(PCL_TEMPLATE_ERR_WRITE_OPEN_FAIL, "Unable to open file '".$p_filename."' in write mode.");
          //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result, 'unable to open file "'.$p_filename.'"');
          return($v_result);
        }
      }
      
      // ----- Set the system values
      $this->_set_system_values();
      
      // ----- Store the working structure
      //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Store working data.");
      $this->working_data['user_data'] = &$p_struct;
      
      $v_result = $this->_generate($this->tokens, $p_struct, $p_output, $fd);

      if ($fd != 0) {
        @fclose($fd);
      }
      
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
    
    // -------------------------------------------------------------------------
    // Function : _generate()
    // Description :
    // Arguments :
    //   $p_output : 'stdout', 'file', 'string'
    //   $p_fd : file descriptor when $p_output='file'
    // -------------------------------------------------------------------------
    function _generate($p_token_list, &$p_struct, $p_output='stdout', $p_fd=0)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_generate', '');
      $v_result = '';
      $v_global_result = '';
      
      foreach ($p_token_list as $v_token) {
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Look for token type '".$v_token['type']."'");
        $v_string = '';
        switch ($v_token['type']) {
          case 'line' :
            $v_string = $v_token['text'];
          break;
          case 'token' :
            // ----- Search for list with matching name
            $v_value = $this->_find_token($v_token['name'], $p_struct);
            
            // ----- Check that value is a string
            if (is_string($v_value)) {
              // ----- Add the value
              $v_string = $v_value;
            }
            else if ($v_value !== FALSE) {
              // ----- Add the value
              $v_string = (string)$v_value;
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Invalid value for token '".$v_token['name']."'");
            }
          break;
          case 'if' :
            // ----- Search for list with matching name
            $v_value = $this->_find_token($v_token['name'], $p_struct);
            //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "look for ".$v_token['type']." condition");
            
            // ----- A token with this name is found
            if ($v_value !== FALSE) {
              // ----- Look for other than an array
              // If the value is an array then the condition is a "condition
              // block". Recursive call.
              // If the value is not an array then the condition is a single
              // condition. We need to change this to a condition block with
              // a single value inside. 
              if (!is_array($v_value)) {
                if (is_string($v_value)) {
                  //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Value is a string");
                  $v_string = $v_value;
                }
                else {
                  //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Value is cast to a string");
                  $v_string = (string)$v_value;
                }
                $v_value = array();
                $v_value[$v_token['name']] = $v_string;
              }

              $v_string = $this->_generate($v_token['tokens'], $v_value, $p_output, $p_fd);
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "No value for token '".$v_token['name']."'");
            }
          break;
          case 'ifnot' :
            // ----- Search for list with matching name
            $v_value = $this->_find_token($v_token['name'], $p_struct);
            //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "look for ".$v_token['type']." condition");
            
            // ----- Look if found
            // If found, all the block is ignored.
            // If not found then the recursive call is done with the same 
            // struct
            if ($v_value === FALSE) {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Token ifnot not found. Call recursive with same struct.");
              $v_string = $this->_generate($v_token['tokens'], $p_struct, $p_output, $p_fd);
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "No value for token '".$v_token['name']."'; Skip ifnot block.");
            }
          break;
          case 'list' :
            // ----- Search for list with matching name
            $v_value = $this->_find_token($v_token['name'], $p_struct);
            
            // ----- Check that value is an array
            if (is_array($v_value)) {
//              $v_string = $this->_generate_list($v_token['tokens'], $v_value, $p_output, $p_fd);
              $v_string = $this->_generate($v_token['tokens'], $v_value, $p_output, $p_fd);
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Invalid value for token '".$v_token['name']."', or empty list");
              //$v_string = $this->_generate_list($v_token['tokens'], array(), $p_output, $p_fd);
            }
          break;
          case 'ifempty' :
            if (sizeof($p_struct) == 0) {
              $v_value = $this->_find_token($v_token['name'], $p_struct);
              if (is_array($v_value)) {
                $v_string = $this->_generate($v_token['tokens'], $v_value, $p_output, $p_fd);
              }
              else {
                $v_dummy = array();
                $v_string = $this->_generate($v_token['tokens'], $v_dummy, $p_output, $p_fd);
                unset($v_dummy);
              }
            }
          break;
          case 'ifnotempty' :
            if (sizeof($p_struct) > 0) {
              $v_value = $this->_find_token($v_token['name'], $p_struct);
              if (is_array($v_value)) {
                $v_string = $this->_generate($v_token['tokens'], $v_value, $p_output, $p_fd);
              }
              else {
                $v_dummy = array();
                $v_string = $this->_generate($v_token['tokens'], $v_dummy, $p_output, $p_fd);
                unset($v_dummy);
              }
            }
          break;
          case 'item' :
            $v_string = '';
            foreach ($p_struct as $v_elt) {
              $v_temp_str = $this->_generate($v_token['tokens'], $v_elt, $p_output, $p_fd);
              if ($v_temp_str === 0) {
                break;
              }         
              $v_string .= $v_temp_str;     
            }
          break;
          case 'include' :
            // ----- Search for include informations with matching name
            $v_value = $this->_find_token($v_token['name'], $p_struct);
            
            // ----- Check that value is an array
            if (   is_array($v_value)
                && (isset($v_value['filename']))) {
              if (isset($v_value['values'])) {
                $v_tokens_inc = $v_value['values'];
              }
              else {
                $v_tokens_inc = array();
              }
              $v_string = $this->_generate_include($v_value['filename'],
                                                   $v_tokens_inc,
                                                   $p_output, $p_fd);              
            }
            else {
              //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Invalid value for token '".$v_token['name']."' specific array expected");
              // TBC : error management ...
            }
          break;
          default :
            //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "No rule to manage '".$v_token['type']."'");
          break;
        }
        
        if (!is_string($v_string)) {
          $v_result = 0;
          //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
          return($v_result);
        }
      
        switch ($p_output) {
          case 'stdout' :
            echo $v_string;
          break;
          case 'file' :
            @fwrite($p_fd, $v_string);
          break;
          case 'string' :
            $v_global_result .= $v_string;
          break;
          default :
            $this->_error_log(PCL_TEMPLATE_ERR_INVALID_PARAMETER, "Unsupported parameter '".$p_output."'");
            $v_result = 0;
            //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
            return($v_result);
          break;
        }
      }

      if ($p_output == 'string') {
        $v_result = $v_global_result;
      }
      else {
        $v_result = PCL_TEMPLATE_ERR_NO_ERROR;
      }
      
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
    
    // -------------------------------------------------------------------------
    // Function : _generate_include()
    // Description :
    // Arguments :
    //   $p_output : 'stdout', 'file', 'string'
    //   $p_fd : file descriptor when $p_output='file'
    // -------------------------------------------------------------------------
    function _generate_include($p_filename, &$p_struct, $p_output='stdout', $p_fd=0)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_generate_include', "filename='".$p_filename."'");
      $v_result = '';
      
      // ----- Create the template object
      $v_template = new PclTemplate();
      
      // ----- Set the delimiters
      // This is needed if delimiters where changed in main file
      $v_template->token_start = $this->token_start;
      $v_template->token_stop = $this->token_stop;
      
      // ----- Parse the template file
      if (($v_result = $v_template->parseFile($p_filename)) != PCL_TEMPLATE_ERR_NO_ERROR) {
        $this->error_list = array_merge($this->error_list,
                                        $v_template->error_list);
        $v_result = 0;
        //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
        return($v_result);
      }
      
      // ----- Set the system values
      $v_template->_set_system_values();
      
      // ----- Store the global values
      $v_template->setGlobals($this->global_values);
      
      // ----- Store working data in included template
      //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Store working data in included template");
      $v_template->working_data['user_data'] = &$this->working_data['user_data'];

      // ----- Generate result
      $v_result = $v_template->_generate($v_template->tokens, $p_struct,
                                         $p_output, $p_fd);
      
      // ----- Unset
      unset($v_template);

      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Function : _find_token()
    // Description :
    //   This function looks in the $p_struct if a token with name
    //   $p_token_name exists and return the value associated with the token
    //   name.
    //   The token name can be a single word, or can be a specific name.
    //   When a single word begins with a dot (.) it means that the requested
    //   value is relative to the first level of the user data structure. This
    //   user data struct was stored in the wizard_data.
    //   When the single word begins with a $ it means that the requested value
    //   is a PclTemplate system value. These words are reserved keywords that
    //   identify some specific values like template filename, path, ...
    // Arguments :
    // -------------------------------------------------------------------------
    function _find_token($p_token_name, &$p_struct)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_find_token', 'token="'.$p_token_name.'"');
      
      if (!is_array($p_struct)) {
        $v_result = FALSE;
        //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result, "argument is not an array");
        return($v_result);
      }
      
      // ----- Look for root values (user values defined in root level)
      $v_type = substr($p_token_name, 0, 1);
      // Root values are identified by a '.' at the beginning
      // In order to use global values, we need to use working data
      // TBC : This should be change to way to access hierarchical data
      //       in the future
      // USE of '.' is DEPRECATED. Please use '$' for system values
      if ($v_type == '.') {
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Token name is a system value");
        $v_struct = &$this->working_data['user_data'];
        $p_token_name = substr($p_token_name, 1);
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Resulting token_name is '".$p_token_name."'");
      }
      
      // ----- Look for globally defined tokens
      elseif ($v_type == '%') {
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Token name is a global value");
        $v_struct = &$this->global_values;
        $p_token_name = substr($p_token_name, 1);
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Resulting token_name is '".$p_token_name."'");
      }
      
      // ----- Look for system reserved tokens
      elseif ($v_type == '$') {
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Token name is a system value");
        $v_struct = &$this->system_values;
        $p_token_name = substr($p_token_name, 1);
        //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Resulting token_name is '".$p_token_name."'");
      }
      
      // ----- Look for single word token
      else {
        $v_struct = &$p_struct;
      }
      
      if (isset($v_struct[$p_token_name])) {
          //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, 1, "found by direct index");
          return($v_struct[$p_token_name]);
      }
      
      if (is_array($v_struct)) {
        foreach ($v_struct as $v_key => $v_item) {
          //--(MAGIC-PclTrace)--//PclTraceFctMessage(__FILE__, __LINE__, 3, "Look for value with key '".$v_key."'");
          if (strtolower($v_key) == $p_token_name) {
            //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, 1, "found by strtolower");
            return($v_item);
          }
        }
      }

      $v_result = FALSE;
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result, "not found");
      return($v_result);
    }
    // -------------------------------------------------------------------------
    
    // -------------------------------------------------------------------------
    // Function : _is_keyword()
    // Description :
    // Arguments :
    // -------------------------------------------------------------------------
    function _is_keyword($p_token)
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_is_keyword', 'token="'.$p_token.'"');
      $v_pcltemplate_keywords = array(
                                  'token',
                                  'list', 'endlist',
                                  'item', 'enditem',
                                  'ifnotempty', 'endifnotempty',
                                  'ifempty', 'endifempty',
                                  'if', 'endif',
                                  'ifnot', 'endifnot',
                                  'include');
      $v_result = in_array($p_token, $v_pcltemplate_keywords);
      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
    
    // -------------------------------------------------------------------------
    // Function : _set_system_values()
    // Description :
    // Arguments :
    // -------------------------------------------------------------------------
    function _set_system_values()
    {
      //--(MAGIC-PclTrace)--//PclTraceFctStart(__FILE__, __LINE__, 'PclTemplate::_set_system_values');
      $v_result = 1;
      
      // ----- Template file information
      $this->system_values['filename'] = '';
      $this->system_values['filepath'] = '';
      $this->system_values['path_from_root'] = '';
      $this->system_values['path_document_root'] = PCL_DOCUMENT_ROOT;
      if ($this->template_name != '') {
        // ----- Template file info from filesystem
        $v_att = pathinfo($this->template_name);
        $this->system_values['filename'] = $v_att['basename'];
        $this->system_values['filepath'] = $v_att['dirname'];
      
        // ----- Template file relative path
        if (PCL_DOCUMENT_ROOT != '') {
//        if (isset($_SERVER['DOCUMENT_ROOT'])) {
//          $v_doc_root = $_SERVER['DOCUMENT_ROOT'];
          $v_doc_root = PCL_DOCUMENT_ROOT;
          if (substr($v_doc_root, -1) != '/') {
            $v_doc_root .= '/';
          }
          $v_temp = strtr($this->template_name, '\\', '/');
          $v_pos = strpos($v_temp, $v_doc_root);
          if (($v_pos !== FALSE) && ($v_pos == 0)) {
            $v_len = strlen($v_doc_root);
            $this->system_values['path_from_root'] = dirname(substr($v_temp, $v_len-1));
          }
        }
      }
      
      // ----- Date
      $this->system_values['date'] = date('Y/m/d h:i:s');

      //--(MAGIC-PclTrace)--//PclTraceFctEnd(__FILE__, __LINE__, $v_result);
      return($v_result);
    }
    // -------------------------------------------------------------------------
    
    // --------------------------------------------------------------------------------
    // Function : _error_name()
    // Description :
    // Parameters :
    // --------------------------------------------------------------------------------
    function _error_name($p_code)
    {
      $v_name = array (  PCL_TEMPLATE_ERR_NO_ERROR => 'PCL_TEMPLATE_ERR_NO_ERROR'
                        ,PCL_TEMPLATE_ERR_GENERIC => 'PCL_TEMPLATE_ERR_GENERIC'
                        ,PCL_TEMPLATE_ERR_SYNTAX => 'PCL_TEMPLATE_ERR_SYNTAX'
                        ,PCL_TEMPLATE_ERR_READ_OPEN_FAIL => 'PCL_TEMPLATE_ERR_READ_OPEN_FAIL'
                        ,PCL_TEMPLATE_ERR_WRITE_OPEN_FAIL => 'PCL_TEMPLATE_ERR_WRITE_OPEN_FAIL'
                      );

      if (isset($v_name[$p_code])) {
        $v_value = $v_name[$p_code];
      }
      else {
        $v_value = $p_code.'(NoName)';
      }
  
      return($v_value);
    }
    // --------------------------------------------------------------------------------

    // --------------------------------------------------------------------------------
    // Function : _error_log()
    // Description :
    // Parameters :
    // --------------------------------------------------------------------------------
    function _error_log($p_error_code=PCL_TEMPLATE_ERR_NO_ERROR, $p_error_string='')
    {
      if (   (!isset($this->error_list))
          || (!is_array($this->error_list))
          || (sizeof($this->error_list) == 0)) {
        $this->error_list = array();
        $v_index = 0;
      }
      else {
        $v_index = sizeof($this->error_list);
      }
      
      $this->error_list[$v_index]['code'] = $p_error_code;
      $this->error_list[$v_index]['text'] = $p_error_string;
      $this->error_list[$v_index]['date'] = date('d/m/Y H:i:s');
      
    }
    // --------------------------------------------------------------------------------
  
    // --------------------------------------------------------------------------------
    // Function : _error_reset()
    // Description :
    // Parameters :
    // --------------------------------------------------------------------------------
    function _error_reset()
    {
      unset($this->error_list);
      $this->error_list = array();
    }
    // --------------------------------------------------------------------------------
  
  }
  // ---------------------------------------------------------------------------
?>
