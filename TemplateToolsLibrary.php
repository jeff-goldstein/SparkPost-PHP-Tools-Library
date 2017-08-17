<?php {
    ini_set('memory_limit', '512M');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    /* Copyright 2017 Jeff Goldstein
    
    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
    
    http://www.apache.org/licenses/LICENSE-2.0
    
    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License. 
    
    File: SubDataCheckLibrary.php
    Purpose: Compare Template Substitution Fields against Substitution Data being sent to the template.
    The main function, SubDataCheck is meant to be called with the substitution data and/or global substitution data just before adding it into the transmission call.  
    It will check to make sure that there is a corresponding substitution field for each field the template is looking for.
    
    EndPoint (1)
    SubDataCheck, the main application is expecting the following parameters:
    
    Paramter Name                Required Y/N        	Notes
    --------------------------    ------------        	-------------------------------------------------------------------------------------------------------------------------------------------------
    apikey								Y               This application uses your account to get the Template from your account.  It needs an API key with the ability to read templates
    apiroot                           	Y               For SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL
    template                          	Y               The Template ID you are validating against            
    recsub                            	Y               This can be an empty string, but if you send data you must send it in the following format
    												  	{"substitution_data" : {fields.........arrays......etc}}
    globalsub                        	Y               This can be an empty string, but if you send data you must send it in the following format
    												  	{"substitution_data" : {fields.........arrays......etc}}
    substitutionItemList            	Y               An empty array that SubDataCheck will fill with a list of all fields found in the rec and global substitution data blocks
    templateItemList                	Y               An empty array that SubDataCheck will fill with a list of all fields found in the template
    missingFields                    	Y               An empty array that SubDataCheck will fill with a list of any fields found in the template but missing from the recipient/global data or optionally, empty/NULL
    addEmptyFieldstoMissingListFlag		Y				If you want empty/Null substitution fields to be added to the MissingFields list send TRUE; otherwise False
    
    
    EndPoint (2)
    IsJson, is a short function that allows you to validate the structure of your data (recipient/global) before sending it to SubDataCheck.  Just like SubDataCheck, the substitution_data block must be surrounded by {}  
     
    Paramter Name                Required Y/N        	Notes
    --------------------------    ------------        	-------------------------------------------------------------------------------------------------------------------------------------------------
    string                            	Y               The function will validate the string to make sure they are properly formatted JSON
    
    
    EndPoint (3)
    BuildTemplateFields, will scan a template and build an array with all of the fields found in the template.
    
    Paramter Name                Required Y/N        	Notes
    --------------------------    ------------        	-------------------------------------------------------------------------------------------------------------------------------------------------
    apikey								Y               This application uses your account to get the Template from your account.  It needs an API key with the ability to read templates
    apiroot                           	Y               For SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL
    template                          	Y               The Template ID you are requesting the field list from              
    templateItemList                	Y               An empty array that SubDataCheck will fill with a list of all fields found in the template
    filename							N				If a filename is sent to the function, it will dump the templateItemList out to that file
      
    
    Warning Notes:  
    1) When decoding the substitution fields, PHP creates index fields into the array.  Ultimately, those numbers are added into the list of substitution data fields.  Becuase the
    user may use numeric field names as well, I'm leaving those indexes in the list instead of trying to figure out if they are PHP decoded generated or field names the user created.
    
    2) There is only ONE entry created for each atomic field name within the global/recipient data sets!!!  This program does NOT track the full json structure name.  Let's look at the first entry of the following JSON structure:
       
       Recipient Substitution Data:
       "ProductList":[  
               {  
                  "item_name":"camp_microphone",
                  "long_description":"Comfortable sound isolating sleeves block up to 37 dB of ambient noise. Sound isolation technology prevents outside noise from interfering with your listening experience, whether on-stage or on-the-go. Camp Sound Isolating Earphones require a proper fit to achieve the best sound.",
                  "price":"102.35",
                  "savings":"12.87",
                  "font_color":"black",
                  "featureList":[  
                     {  
                        "description":"",
                        "font_color" :"black"
                     },
                     {  
                        "description":"Volume Control",
                        "font_color":"blue"
                     },
                     {  
                        "description":"Noise Isolating",
                        "font_color":"red"
                     }
                  ]
               },
    
       Global Substitution Data:
       		"subject": "Your Purchase History As Requested",
        	"from" : "jeff.goldstein@mail.geekwithapersonality.com",
        	"DelivChannel": "trans",
        	"header_background_color": "#abcdef",
        	"text_decoration_color": "#378cd2",
        	"hover_color": "#82b450",
       		"description":"",
       		
       		
       o No matter how many products reside within the 'ProductList array' this application will only capture one entry for each field name and they will be at the lowest atomic level.  
         In this example: ProductList, item_name, description, long_description, price, savings, featureList, font_color, subject, from, DelivChannel, header_background_color, text_decoration_color and hover_color will all be captured.
       o Notice that 'description' and 'font_color' will only be listed once and there is no ProductList.featureList.description or ProductList.featureList.font_color entry.
       o If the same field name is used in both the global and recipient data sets, there will still only be ONE entry.
       o This means that the first time a field name is found; the corresponding value for that field will be what is used for further comparisons.  In this example, because Recipient data will be scanned first,  
         when the system compares the Template field 'description' with the scanned recipient/global fields, it will be marked as 'empty', because the first occurrence  of 'description' is empty.
       o Because SparkPost puts uses an order of precedence  where recipient data overrides global data, this application will scan the recipient data first for fields and use those values. 
    
       One caveat of all of this is, that when using the 'addEmptyFieldstoMissingListFlag', you may not get 100% accurate results because only the values of the first time a field name is found/scanned will be used and may not represent 
       the full dataset. 
    */
    
    function SubDataCheckLibraryEndPoints ()
    {
    	echo "\n\nFile: SubDataCheckLibrary.php";
    	echo "\nPurpose: Compare Template Substitution Fields against Substitution Data being sent to the template.";
    	echo "\nThe main function, SubDataCheck is meant to be called with the substitution data and/or global substitution data just before adding it into the transmission call.";  
    	echo "\nIt will check to make sure that there is a corresponding substitution field for each field the template is looking for.";
    	echo "\n";
    	echo "\nEndPoint (1)";
    	echo "\nSubDataCheck, the main application is expecting the following parameters:";
    	echo "\n";
    	echo "\nParamter Name\t\t\tRequired Y/N\tNotes";
    	echo "\n--------------------------\t------------\t-------------------------------------------------------------------------------------------------------------------------------------------------";
    	echo "\napikey\t\t\t\t\tY\tThis application uses your account to get the Template from your account.  It needs an API key with the ability to read templates";
    	echo "\napiroot\t\t\t\t\tY\tFor SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL";
    	echo "\ntemplate\t\t\t\tY\tThe Template ID you are validating against";   
    	echo "\nrecsub\t\t\t\t\tY\tThis can be an empty string, but if you send data you must send it in the following format";
    	echo "\n\t\t\t\t\t\t{'substitution_data' : {fields.........arrays......etc}}";
    	echo "\nglobalsub\t\t\t\tY\tThis can be an empty string, but if you send data you must send it in the following format";
    	echo "\n\t\t\t\t\t\t{'substitution_data' : {fields.........arrays......etc}}";
    	echo "\nsubstitutionItemList\t\t\tY\tAn empty array that SubDataCheck will fill with a list of all fields found in the rec and global substitution data blocks";
    	echo "\ntemplateItemList\t\t\tY\tAn empty array that SubDataCheck will fill with a list of all fields found in the template";
    	echo "\nmissingFields\t\t\t\tY\tAn empty array that SubDataCheck will fill with a list of any fields found in the template but missing from the recipient/global data or optionally, empty/NULL";
    	echo "\naddEmptyFieldstoMissingListFlag\t\tY\tIf you want empty/Null substitution fields to be added to the MissingFields list send TRUE; otherwise False";
    	echo "\n\n";
    	echo "\nEndPoint (2)";
    	echo "\nIsJson, is a short function that allows you to validate the structure of your data (recipient/global) before sending it to SubDataCheck.  Just like SubDataCheck, the substitution_data block must be surrounded by {}"; 
    	echo "\n"; 
    	echo "\nParamter Name\t\t\tRequired Y/N\tNotes";
    	echo "\n--------------------------\t------------\t-------------------------------------------------------------------------------------------------------------------------------------------------";
    	echo "\nstring\t\t\t\t\tY\tThe function will validate the string to make sure they are properly formatted JSON";
    	echo "\n\n";
    	echo "\nEndPoint (3)";
    	echo "\nBuildTemplateFields, will scan a template and build an array with all of the fields found in the template.";
    	echo "\n";
    	echo "\nParamter Name\t\t\tRequired Y/N\tNotes";
    	echo "\n--------------------------\t------------\t-------------------------------------------------------------------------------------------------------------------------------------------------";
    	echo "\napikey\t\t\t\t\tY\tThis application uses your account to get the Template from your account.  It needs an API key with the ability to read templates";
    	echo "\napiroot\t\t\t\t\tY\tFor SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL";
    	echo "\ntemplate\t\t\t\tY\tThe Template ID you are requesting the field list from";              
    	echo "\ntemplateItemList\t\t\tY\tAn empty array that SubDataCheck will fill with a list of all fields found in the template";
    	echo "\nfilename\t\t\t\tN\tIf a filename is sent to the function, it will dump the templateItemList out to that file\n\n"; 
    	echo "\nWarning Note:  When decoding the substitution fields, PHP creates index fields into the array.  Ultimately, those numbers are added into the list of substitution data fields.";
    	echo "\nBecuase the user may use numeric field names as well, I'm leaving those indexes in the list instead of trying to figure out if they are PHP decoded generated or field names the user created.\n\n";  
    }
    
    function isJson($string)
    {
        json_decode($string);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $json_check_result = 'No errors';
                break;
            case JSON_ERROR_DEPTH:
                $json_check_result = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $json_check_result = 'Underflow or the modes mismatch, check brackets?';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $json_check_result = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $json_check_result = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $json_check_result = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $json_check_result = 'Unknown error';
                break;
        }
        return $json_check_result;
    }
    
    function BuildKeywordArray (&$keywords)
    {
    	$keywords = array("and", "break", "do", "else", "elseif", "end", "false", "for", "function", "if", "in", "local", "nil", "not", "!", "or", "each", "repeat", "return", "then", "true", "until", "while", '"', '""');
        array_push($keywords, "==", "=", "!=", "<", ">", "opening_double_curly", "closing_double_curly", "opening_triple_curly", "closing_triple_curly", "loop_var", "loop_vars", "render_dynamic_content", "dynamic_html", "dynamic_text", "]", ")", "])");
    }
    
    function GetTemplate ($apikey, $apiroot, $template, &$storedRawTemplate)
    {
        $curl = curl_init();
        $url  = $apiroot . "templates/" . $template;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: $apikey",
                "cache-control: no-cache",
                "content-type: application/json"
            )
        ));
        
        $response          = curl_exec($curl);
        $encodedResponse   = json_decode($response, true);
        if(curl_exec($curl) === false) 
        {
        	$errorFromAPI      = curl_error($curl);
        }
        else
        {
        	$errorFromAPI = "No Error";
        }
        $storedRawTemplate = $encodedResponse["results"]["content"]["html"];
        curl_close($curl);
        return $errorFromAPI;
    }
    
    function NewWordValidation($word, $substitutionItemList, $keywords, &$missingFields, &$templateItemList, $addEmptyFieldstoMissingListFlag)
    {
        if (!in_array($word, $keywords) && (mb_strlen($word) > 0)) //Check against keywords first
        {
        	if (!array_key_exists($word, $templateItemList)) //Check against already found items in the template
            {
            	$templateItemList[$word] = NULL;   // Default to NULL, since we don't know which function is calling us so we leave the array value empty
            	if ($addEmptyFieldstoMissingListFlag !== "templatescan")
            	{
            		if (array_key_exists($word, $substitutionItemList)) $templateItemList[$word] = "Field Found";  // Called by an application where the substitutionlist was built and we found a matching field
                	if (!array_key_exists($word, $substitutionItemList)) //Check against items from the substitution field array
                	{
                	// No matching Substitution Field Found
                    	$templateItemList[$word] = "Missing Field";
                    	$missingFields[$word] = "Missing Field";
                	}
                	else if ($substitutionItemList[$word] == "Empty")
                	{
                    	$templateItemList[$word] = $substitutionItemList[$word]; 
                    	if ($addEmptyFieldstoMissingListFlag)
						{
                    		$missingFields[$word] = $substitutionItemList[$word];
                    	}
                    }
                }
            }
        }
    }
    
    function CompareTemplateFields($storedRawTemplate, $substitutionItemList, $keywords, &$missingFields, &$templateItemList, $addEmptyFieldstoMissingListFlag)
    {      
        $initialParse   = "/{{(.*)}}/U";
        $getNumberFound = preg_match_all($initialParse, $storedRawTemplate, $shreded);
        $shreded        = $shreded[1];
        $scanText       = "/[{\s\[\].()!]/";
        foreach ($shreded as $shredkey => $shredvalue) {
            $index  = 0;
            $start  = 0;
            $end    = 0;
            $length = mb_strlen($shredvalue);
            while ($index < $length) {
                $currentChar = mb_substr($shredvalue, $index, 1);
                $stop        = preg_match($scanText, $currentChar);
                if ($stop && $index != 0) {
                    $word = mb_substr($shredvalue, $start, $end - $start);
                    if ((mb_substr($word,0,1) != "'") && (mb_substr($word, 0, 1) != '"'))  // check to see if the item is a constant ie. within quotes
                    {
                    	NewWordValidation($word, $substitutionItemList, $keywords, $missingFields, $templateItemList, $addEmptyFieldstoMissingListFlag);
                    	$start = $index + 1;
                    }
                }
                if ($stop && $index == 0)
                    $start++;
                $index++;
                $end++;
            }
            $word = mb_substr($shredvalue, $start, $end - $start);
            if ((mb_substr($word,0,1) != "'") && (mb_substr($word, 0, 1) != '"'))  // check to see if the item is a constant ie. within quotes
            {
            	NewWordValidation($word, $substitutionItemList, $keywords, $missingFields, $templateItemList, $addEmptyFieldstoMissingListFlag);
            }
        }
    }
    
    function BuildSubstitutionArray($subdataArray, &$substitutionItemList)
    {
        //Build the list of substitutions fields
        $key = NULL;
        if (is_array($subdataArray)) {
            foreach ($subdataArray as $key => $value) {
                if ($value == "" || $value == NULL) 
                {
                    $substitutionItemList[$key] = "Empty"; 
                }
                else 
                {
                    $substitutionItemList[$key] = "Data Found";
                }
                //$substitutionItemList[$key] = "anything";
                if ($key != NULL) {
                    BuildSubstitutionArray($value, $substitutionItemList);
                }
            }
        }
        if ($key != NULL) {
            BuildSubstitutionArray($value, $substitutionItemList);
        }
    }
    
    function ConcatRecipientGlobalFields ($recsub, $globalsub, &$allsub)
    {
    //
    // Now let's see what data we actually have, and create one substitution group for the comparisons     
        if ((mb_strlen($globalsub) > 1) && (mb_strlen($recsub) > 1)) //we have both global and personal substitution data
            {
            // We will concatenate recipient data after the global data into one substitution_data object
            // We need to remove/change some quotes, commas, brackets to do this
            
            // Since we had a full JSON structure, we need to make some changes to contactentate the two structures into one JSON structure
            // We will place recsub at the top because recipient data takes precednece over global data. 
            $pos       = mb_strpos($recsub, "{"); // Find first bracket.  This should be the first character
            $recsub = mb_substr($recsub, $pos + 1); // Remove first bracket
            $pos       = mb_strpos($recsub, "{"); // Find the begining of the actual fields after the 'substitution_data' key name
            $recsub = mb_substr($recsub, $pos); // Strip 'substitution_data but leave the first bracket.  We still need a properly formed JSON for decoding
            $recsub = mb_substr($recsub, 0, -2); //Remove the last two brackets so it's open to concatenate globalsub
            $recsub = $recsub . ","; //add closing comma before globalsub
            
            // Now change the global substitution data   
            $pos    = mb_strpos($globalsub, "{"); // Find first bracket.  This should be the first character
            $globalsub = mb_substr($globalsub, $pos + 1); // Remove first bracket
            $pos    = mb_strpos($globalsub, "{"); // Find the begining of the actual fields after the 'substitution_data' key name
            $globalsub = mb_substr($globalsub, $pos + 1); // Strip 'substitution_data and opening bracket since the Global Substitution will have that bracket
            if (mb_substr($globalsub, -1) == ",")
                $globalsub = mb_substr($globalsub, 0, -1); //remove trailing comma user entered
            $globalsub = mb_substr($globalsub, 0, -1); //remove trailing }
            
            $subEntry  = $recsub . $globalsub;  //the SparkPost template generator places a higher priority to recipient data so we will place that first
        }
        if ((mb_strlen($globalsub) > 1) && (mb_strlen($recsub) < 1)) //global substitution only
            {
            $pos       = mb_strpos($globalsub, "{"); // Find first bracket.  This should be the first character
            $globalsub = mb_substr($globalsub, $pos + 1); // Remove first bracket
            $pos       = mb_strpos($globalsub, "{"); // Find the begining of the actual fields after the 'substitution_data' key name
            $globalsub = mb_substr($globalsub, $pos); // Strip 'substitution_data
            $subEntry  = trim($globalsub); //remove any white space
            $subEntry  = mb_substr($subEntry, 0, -1); //Remove the last bracket that matched the leading bracket
        }
        if ((mb_strlen($globalsub) < 1) && (mb_strlen($recsub) > 1)) //personal substitution only
            {
            //Expecting full json structure with beginning and ending {} backets.
            $pos    = mb_strpos($recsub, "{"); // Remove first bracket
            $recsub = mb_substr($recsub, $pos + 1); // Remove first bracket
            $pos    = mb_strpos($recsub, "{"); // Find the begining of the actual fields after the 'substitution_data' key name
            $recsub = mb_substr($recsub, $pos); // Strip 'substitution_data
            if (mb_substr($recsub, -1) == ",")
                $recsub = mb_substr($recsub, 0, -1); //remove trailing comma user entered
            if (mb_substr($recsub, -1) == "}")
                $recsub = mb_substr($recsub, 0, -1); //remove trailing comma user entered
            $recsub   = trim($recsub); //remove any white space
            $subEntry = $recsub;
        }
        // Create an empty array object.  Will fail at the begining of the loop
        if ((mb_strlen($globalsub) < 1) && (mb_strlen($recsub) < 1)) {
            $recsub   = json_decode("{}");
            //$subEntry = '{"substitution_data":' . $recsub . '}';
            $subEntry = json_encode($recsub);
            echo "\n\n**No Recipient or Global Substitution Data Found**";
        }
        
        $allsub = json_decode($subEntry, TRUE);
	}
	
 	function BuildTemplateFields($apikey, $apiroot, $template, &$templateItemList, $filename = NULL)
    {   
        $substitutionItemList = array();
        $missingFields = array();
        $storedRawTemplate = NULL;
        BuildKeywordArray ($keywords);
        GetTemplate ($apikey, $apiroot, $template, $storedRawTemplate);
        
        $initialParse   = "/{{(.*)}}/U";
        $getNumberFound = preg_match_all($initialParse, $storedRawTemplate, $shreded);

        $shreded        = $shreded[1];
        $scanText       = "/[{\s\[\].()!]/";
        foreach ($shreded as $shredkey => $shredvalue) {
            $index  = 0;
            $start  = 0;
            $end    = 0;
            $length = mb_strlen($shredvalue);
            while ($index < $length) {
                $currentChar = mb_substr($shredvalue, $index, 1);
                $stop        = preg_match($scanText, $currentChar);
                if ($stop && $index != 0) {
                    $word = mb_substr($shredvalue, $start, $end - $start);
                    if ((mb_substr($word,0,1) != "'") && (mb_substr($word, 0, 1) != '"'))  // check to see if the item is a constant ie. within quotes
                    { 
                    	NewWordValidation($word, $substitutionItemList, $keywords, $missingFields, $templateItemList, "templatescan");
                    	$start = $index + 1;
                    }
                }
                if ($stop && $index == 0)
                    $start++;
                $index++;
                $end++;
            }
            $word = mb_substr($shredvalue, $start, $end - $start);
            if ((mb_substr($word,0,1) != "'") && (mb_substr($word, 0, 1) != '"')) // check to see if the item is a constant ie. within quotes
            {
            	NewWordValidation($word, $substitutionItemList, $keywords, $missingFields, $templateItemList, "templatescan");
            }
        }
        if ($filename != NULL)
        {
        	$templatenames = NULL;
        	foreach (array_keys($templateItemList) as $paramName)
        		$templatenames .= $paramName . "\n";
        	file_put_contents ($filename, $templatenames, LOCK_EX );
        }
    }
	
    function SubDataCheck($apikey, $apiroot, $template, $recsub, $globalsub, &$substitutionItemList, &$templateItemList, &$missingFields, $addEmptyFieldstoMissingListFlag)
    {
        
        $storedRawTemplate = NULL;
        $keywords = array();
        
        BuildKeywordArray ($keywords);
        ConcatRecipientGlobalFields ($recsub, $globalsub, $allsub);  
        BuildSubstitutionArray($allsub, $substitutionItemList);
        GetTemplate ($apikey, $apiroot, $template, $storedRawTemplate);
        CompareTemplateFields($storedRawTemplate, $substitutionItemList, $keywords, $missingFields, $templateItemList, $addEmptyFieldstoMissingListFlag);
        
    }
    
} //end of program
?>
