PHP RPC Client/Server Example
---
**Author:** @lcherone

**What does it do? (For the sake of testing out the extension)**

A very simple few-lines RPC endpoint and client which runs CRUD operations on a `/tmp` database.

**Uses** 

 - [Yet Another RPC Framework](http://php.net/manual/en/book.yar.php) (PECL extension)
 - [RedBeanPHP](http://www.redbeanphp.com) for endpoint database operations.

**To install the extension:**

    sudo apt-get install php5-dev libcurl4-gnutls-dev
    sudo pecl install yar

Then create a new extension apache config file for `yar.so`

    echo "extension=yar.so" > /etc/php5/apache2/conf.d/yar.ini

Restart apache...

**IMO**

[Plinker](https://bitbucket.org/plinker/example) is better, way more secure, namespaced and code accessible, 100% PHP so no extension required and does the same thing and more.