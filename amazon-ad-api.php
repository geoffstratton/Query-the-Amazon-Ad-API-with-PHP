<?php
 
$booksarray = array(
       array("1607103168","1/2016",5),   // Five Novels by Mark twain
       array("1631060244","1/2016",5),   // Complete Works of William Shakespeare
       array("B008P96TI0","1/2016",4)    // The Collected Stories of Philip K. Dick, Vol. I
);
 
$querystrings = array('');
$params = array();
$pairs = array();
$signed_urls = array();
$returned_asins = array();

// Your AWS Access Key ID, from the AWS Your Account page
$aws_access_key_id = "secret-key";
 
// Your AWS Secret Key corresponding to the above ID, from the AWS Your Account page
$aws_secret_key = "double-secret-key";
 
// The region you are interested in
$endpoint = "webservices.amazon.com";
 
$uri = "/onca/xml";
 
// Build an array of ASINs, in sets of 10, as strings with comma separators
$j = 0;
for ($i = 0; $i < count($booksarray); $i++) {
    if ($i > 0 && $i % 10 == 0) {
        // Element index is divisible by 10, so we need a new array element
        array_push($querystrings,''); // get rid of undefined offset warning
        $j++;
    }
    $querystrings[$j] .= $booksarray[$i][0].',';
}
 
// Build query strings using comma-separated $querystrings
for ($k = 0; $k < count($querystrings); $k++) {
    array_push($params, array(
        "Service" => "AWSECommerceService",
        "Operation" => "ItemLookup",
        "AWSAccessKeyId" => $aws_access_key_id,
        "AssociateTag" => "geofstra-20",
        "ItemId" => rtrim($querystrings[$k],','),
        "IdType" => "ASIN",
        "ResponseGroup" => "Images,ItemAttributes",
        )
    );
}
 
//print_r($params);
 
// Sign each query string
foreach ($params as $param) {
    // Set current timestamp if not set
    if (!isset($param["Timestamp"])) {
        $param["Timestamp"] = gmdate('Y-m-d\TH:i:s\Z');
    }
     
    // Sort the parameters by key
    ksort($param);
 
    $pairs = array();
 
    foreach ($param as $key => $value) {
        array_push($pairs, rawurlencode($key)."=".rawurlencode($value));
    }
 
    // Generate the canonical query
    $canonical_query_string = join("&", $pairs);
 
    // Generate the string to be signed
    $string_to_sign = "GET\n".$endpoint."\n".$uri."\n".$canonical_query_string;
 
    // Generate the signature required by the Product Advertising API
    $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $aws_secret_key, true));
 
    // Generate the signed URL
    $request_url = 'http://'.$endpoint.$uri.'?'.$canonical_query_string.'&Signature='.rawurlencode($signature);
 
    //echo "<p>Signed URL: \"".$request_url."\"</p>";
 
    array_push($signed_urls, $request_url);
}
 
echo '<table class="booklist">';
echo '<tr>';
echo '<th class="bookdetails">Title/Author/Publisher/ISBN</th>';
echo '<th>Buy</th>';
echo '<th>Pages</th>';
echo '<th>Date Completed</th>';
echo '<th>Rating (of 5)</th>';
echo '</tr>';
 
// Iterate over the <item> tags in each set of 10 results, print non-duplicates
$j = 0; // counter to keep track of valid items
foreach ($signed_urls as $signed_url) {
   
    $xmldoc = file_get_contents("$signed_url");
    $xml = new SimpleXMLElement($xmldoc);
    $count = $xml->Items->Item->count();
    for ($i = 0; $i < $count; $i++) { 
 
        $cur_isbn = "";
        // Cast this to string, otherwise you get an array of SimpleXML objects
        $cur_asin = (string) $xml->Items->Item[$i]->ASIN;
 
        // If ASIN isn't blank or duplicate, print the book's details 
        if ($cur_asin && !(in_array($cur_asin, $returned_asins))) {
             
            // Determine ISBN, if it exists    
            if (empty($xml->Items->Item[$i]->ItemAttributes->ISBN)) {
                $cur_isbn = $xml->Items->Item[$i]->ItemAttributes->EISBN;
            } else {
                $cur_isbn = $xml->Items->Item[$i]->ItemAttributes->ISBN;
            }
 
            echo '<tr>';
            echo '<td class="booktitle"><strong>Title: </strong>'.$xml->Items->Item[$i]->ItemAttributes->Title;
            echo '<br /><strong>Author: </strong>'.$xml->Items->Item[$i]->ItemAttributes->Author;
            echo '<br /><strong>Publisher: </strong>'.$xml->Items->Item[$i]->ItemAttributes->Publisher;
            echo '<br /><strong>ISBN: </strong>'.$cur_isbn.'</td>';
            echo '<td><a href="'.$xml->Items->Item[$i]->DetailPageURL.'">'.
                   '<img src="'.$xml->Items->Item[$i]->MediumImage->URL.'" /></a></td>';
            echo '<td class="pages">'.$xml->Items->Item[$i]->ItemAttributes->NumberOfPages.'</td>';
            echo '<td class="date">'.$booksarray[$j][1].'</td>';
            echo '<td class="rating">'.$booksarray[$j][2].'</td>';
            echo '</tr>';
            array_push($returned_asins, $cur_asin);
            $j++;
        }
    }
}
 
?>
