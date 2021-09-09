# Using the Amazon Ad API with PHP
How to retrieve products from Amazon's inventory using the Amazon Product Advertising API.

This is the code for my [basic implementation](http://www.geoffstratton.com/amazon-reading-list-phpxmljquery) of the [Amazon Product Advertising API](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/Welcome.html).

This script fetches a list of products in batches of 10 from Amazon's enormous products database, using their unique identifier (ASIN). 

Originally I wrote this script to keep track of the [books I read every year](http://www.geoffstratton.com/amazon-reading-list-phpxmljquery). When I finish each book, I add its ASIN string to my PHP script. The script then performs an ItemLookup request for each book, and Amazon returns each book's information in XML format. From there my script iterates over the results and prints them in a simple table.

In order to run an ItemLookup you need to set up an Amazon Associates account and then [register](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/becomingDev.html) to use the Product Advertising API. Requests and results are exchanged here via [REST](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/AnatomyOfaRESTRequest.html) (the onca/xml URI), although Amazon also supports [SOAP](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/MakingSOAPRequestsArticle.html) (onca/soap) for SOAP/WSDL environments.

To process the results I use PHP's basic built-in [SimpleXML library](http://php.net/manual/en/book.simplexml.php), although you can certainly use [DOMDocument](http://php.net/manual/en/class.domdocument.php) or an equivalent; both SimpleXML and DOMDocument are based on libxml so their behavior is similar. Finally, it's worth reading over the documentation regarding [request limits](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/TroubleshootingApplications.html) to understand how Amazon throttles incoming traffic.

### Performance and Accuracy Concerns

Rather than performing a separate request for each item, it's better to consolidate your requests into [batch jobs](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/BatchandMultipleOperationRequests.html), that is, combined requests for multiple item IDs. Here I'm performing up to 10 (the maximum allowed) ItemLookup operations per request. Since this cuts the number of requests by up to 90%, your product page will load much faster, and you're much less likely to see Amazon's 503 errors caused by overrunning the per-second request limit.

Originally I wrote this script to query by ISBN. While this is adequate for most printed materials, sometimes an ISBN number returns multiple editions of a given book, including Kindle editions, so that instead of the expected 10 results you may receive dozens. Furthermore, Kindle editions with blank ISBN fields are included by default, so if you don't want the duplicates you need to exclude them somehow. I later updated the script to query by Amazon's unique identifier (ASIN).

Another workaround for larger-than-expected result sets with ISBN is to filter the returned XML to eliminate <Item> tags with blank or duplicate ISBN numbers. Previously I used this method with a simple trick where I placed the returned ISBNs in an array and then tested the subsequent item ISBNs against the array contents to detect duplicates. One downside to this approach is that the non-duplicate may not be the desired edition of a given book. Amazon prioritizes the editions of books that are in-print and more likely to sell -- that is the point of the Product Advertising system, after all -- so if you're merely attempting to sell as many items as possible this is probably a workable solution. I'm trying to display the book I actually read here, though, so I switched to using ASIN.

Here's some sample XML returned by an ItemLookup:

```xml
<?xml version="1.0" ?>
<ItemLookupResponse
    xmlns="http://webservices.amazon.com/AWSECommerceService/2011-08-01">
    <OperationRequest>
        <HTTPHeaders>
            <Header Name="UserAgent" Value="Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36"></Header>
        </HTTPHeaders>
        <RequestId>aff5de19-dbac-44f8-84d9-b901111f7f2f</RequestId>
        <Arguments>
            <Argument Name="AWSAccessKeyId" Value="ackThbbbt"></Argument>
            <Argument Name="AssociateTag" Value="geofstra-20"></Argument>
            <Argument Name="IdType" Value="ISBN"></Argument>
            <Argument Name="ItemId" Value="0802779239"></Argument>
            <Argument Name="Operation" Value="ItemLookup"></Argument>
            <Argument Name="ResponseGroup" Value="Images,ItemAttributes,Offers"></Argument>
            <Argument Name="SearchIndex" Value="Books"></Argument>
            <Argument Name="Service" Value="AWSECommerceService"></Argument>
            <Argument Name="Timestamp" Value="2016-03-29T23:45:28.000Z"></Argument>
            <Argument Name="Signature" Value="ackThbbbt"></Argument>
        </Arguments>
        <RequestProcessingTime>0.0327330000000000</RequestProcessingTime>
    </OperationRequest>
    <Items>
        <Request>
            <IsValid>True</IsValid>
            <ItemLookupRequest>
                <IdType>ISBN</IdType>
                <ItemId>0802779239</ItemId>
                <ResponseGroup>Images</ResponseGroup>
                <ResponseGroup>ItemAttributes</ResponseGroup>
                <ResponseGroup>Offers</ResponseGroup>
                <SearchIndex>Books</SearchIndex>
                <VariationPage>All</VariationPage>
            </ItemLookupRequest>
        </Request>
        <Item>
            <ASIN>0802779239</ASIN>
            <DetailPageURL>http://www.amazon.com/Maos-Great-Famine-Devastating-Catastrophe/dp/0802779239%3FSubscriptionId%3DAKIAIBUBR6FVJSMAIMMA%26tag%3Dgeofstra-20%26linkCode%3Dxm2%26camp%3D2025%26creative%3D165953%26creativeASIN%3D0802779239</DetailPageURL>
            <ItemLinks>
                <ItemLink>
                    <Description>Technical Details</Description>
                    <URL>http://www.amazon.com/Maos-Great-Famine-Devastating-Catastrophe/dp/tech-data/0802779239%3FSubscriptionId%3DAKIAIBUBR6FVJSMAIMMA%26tag%3Dgeofstra-20%26linkCode%3Dxm2%26camp%3D2025%26creative%3D386001%26creativeASIN%3D0802779239</URL>
                </ItemLink>
etc.
```

License
---------------
GNU General Public License v3.0
                
Author
---------------
Geoff Stratton
