<?php {
/*
This is a test program for the <library name> library.  This is not an all exclusive
test framework, simply a way to see the output of many of the functions.
*/
    $substitutionItemList = array();
    $templateItemList     = array();
    $missingFields        = array();
    
    $apikey   = "<your api key>";  // Needs Template Read Capabilities
    $apiroot  = "https://api.sparkpost.com/api/v1/";
    $template = "invoice";
    $recsub   = '{
    "substitution_data": {
        "order_id": "2123",
        "order_status": "https://store-phu7azi.mybigcommerce.com/orderstatus.php",
        "first_name": "John",
        "last_name": "Stone",
        "shipping_street_addr1": "123 Main St Apt #2",
        "shipping_street_addr2": "",
        "shipping_city": "Pleasanton",
        "shipping_state": "California",
        "shipping_zip": "94566",
        "shipping_country": "United States",
        "shipping_phone": "(925) 462-3433",
        "billing_street_addr1": "123 Main St Apt #2",
        "billing_street_addr2": "",
        "billing_city": "Pleasanton",
        "billing_state": "California",
        "billing_zip": "94566",
        "billing_country": "United States",
        "order_specific_comments": " Rider weight = 300kg N = Red // Black either side valve stem",
        "sub_total": "£990.00 GBP",
        "shipping": "",
        "grand_total": "£990.00 GBP",
        "productlist": [{
                "item_name": "Custom Wheel Upgrade: Black Spoke Pack",
                "short_description": "",
                "long_description": "",
                "sku": "CWU-50",
                "quantity": "1",
                "item_price": "£50.00 GBP",
                "item_total": "£50.00 GBP",
                "line_details": [{
                        "description": "Black Spoke Upgrade:",
                        "value": "1"
                    },
                    {
                        "description": "Nipple Choice:",
                        "value": "Red"
                    }
                ]
            },
            {
                "item_name": "Dark Energy DMX550-L wheelset: Fat Boy Rim will D-Light you 50mm 1600g",
                "short_description": "",
                "long_description": "",
                "sku": "CWU-50",
                "quantity": "1",
                "item_price": "£940.00 GBP",
                "item_total": "£940.00 GBP",
                "line_details": [{
                        "description": "Drive:",
                        "value": "Shimano/SRAM"
                    },
                    {
                        "description": "Hub Colour:",
                        "value": "Red"
                    },
                    {
                        "description": "Rider Weight",
                        "value": "Above 85kg"
                    }
                ]
            }
        ]
    }}';
    
    $globalsub = '{"substitution_data" : 
    {
        "company_home_url" : "www.sparkpost.com",
        "company_logo" : "https://db.tt/lRplbEmw",
        "logo_height" : "20",
        "logo_width" : "75",
        "header_color" : "#cc6600",
        "header_box_color" : "#fff4ea",
        "order_generic_comments" : "Many thanks for your phone order! You can view your orders build progress, or modify your contact, password & delivery information by logging in to your account using your email address as your user name.",
        "offers" : "",
        "email_address" : "what@address.com"
    }}';
    
    
    //Test Fields to remove/add 
    // Global
    //"company_name" : "Fast Bikes",
    //"company_url" : "Fast Bikes",
    
    // Recipient
    //"billing_phone": "(925) 462-3433",
    
	
	function pretty_array_print($print_array)
    {
    	printf("\n%-50s %s\n", "KEY", "VALUE");
    	$headbreak = str_repeat("=", 45);
    	echo $headbreak . "      ";
    	$headbreak = str_repeat("=", 15);
    	echo $headbreak . "\n";

    	foreach ($print_array as $key => $value) 
    	{
        	printf("%-50s %s\n", $key, $value);
        }
	}
    
    include 'SubDataCheckLibrary.php';
    SubDataCheck($apikey, $apiroot, $template, $recsub, $globalsub, $substitutionItemList, $templateItemList, $missingFields, False);
/*  Paramter Name                	Required Y/N        	Notes
    --------------------------    ------------        	-------------------------------------------------------------------------------------------------------------------------------------------------
    apikey								Y               This application uses your account to get the Template from your account.  It needs an API key with the ability to read templates
    apiroot                           	Y               For SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL
    template                          	Y               The Template ID you are validating against            
    recsub                            	Y               This can be an empty string, but if you send data you must send it in the following format
    												  	{"substitution_data" : {fields.........arrays......etc}}
    globalsub                      		Y               This can be an empty string, but if you send data you must send it in the following format
    												  	{"substitution_data" : {fields.........arrays......etc}}
    substitutionItemList            	Y               An empty array that SubDataCheck will fill with a list of all fields found in the rec and global substitution data blocks
    templateItemList                	Y               An empty array that SubDataCheck will fill with a list of all fields found in the template
    missingFields                  		Y               An empty array that SubDataCheck will fill with a list of any fields found in the template but missing from the recipient/global data or optionally, empty/NULL
    addEmptyFieldstoMissingListFlag		Y				If you want empty/Null substitution fields to be added to the MissingFields list send TRUE; otherwise False
 */
    
    echo "\n\nThe following output is data coming from the SubDataCheck function which checks what fields are missing from input substitution data that the template is using.";
    echo "\nSubDataCheck accepts a parameter to force the search to also identify fields that are empty, they will be denoted with the value of 'Empty' when this data is requested.\n";
    pretty_array_print($missingFields);
    
    echo "\n\nThis is a full list of Fields found within the template.\n";
    pretty_array_print($templateItemList);
    
    echo "\n\nThis is a full list of the substitution fields identified and whether data was found or if it was left empty.\n";
    pretty_array_print($substitutionItemList); 
    
    $templateItemList = array();
    BuildTemplateFields($apikey, $apiroot, $template, $templateItemList, 'fieldoutput.txt');
/*  Paramter Name                Required Y/N        	Notes
    --------------------------    ------------        	-------------------------------------------------------------------------------------------------------------------------------------------------
    apikey								Y               This application uses your account to get the Template from your account.  It needs an API key with the ability to read templates
    apiroot                           	Y               For SparkPost.com this should be: https://api.sparkpost.com/api/v1/  For Enterprise customers, use your unique URL
    template                          	Y               The Template ID you are requesting the field list from              
    templateItemList                	Y               An empty array that SubDataCheck will fill with a list of all fields found in the template
    filename							N				If a filename is sent to the function, it will dump the templateItemList out to that file
*/

    echo "\n\n-----The following output is data coming from the BuildTemplateFields function which produces a list of fields the template is using.";
    echo "\n\nThis function is similar to SubDataCheck, but only builds the list of template fields.\n";
    foreach ($templateItemList as $key => $value) {
        printf("Field: %sn", $key);
                }
    
    $results = isJson($recsub);
    echo "\n\n-----The following output is a sample coming from the function 'isJson' that checks that can check if the passed string is a proper Json structure\n";
    echo "which is needed in both 'SubDataCheck' and 'BuildTemplateFields'";
    echo "\n\nResults recsub isJson: " . $results . "\n\n";
    
    echo "\n\n-----This is what the function 'SubDataCheckLibraryEndPoints' produces.  Which in essence is a -h or /h for the library\n\n";
    SubDataCheckLibraryEndPoints();
}
?>
