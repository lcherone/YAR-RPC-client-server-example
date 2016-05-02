PHP RPC Client/Server Example
---
**Author:** @lcherone

**What does it do?**
It was originally just an experiment with the YAR PECL extension, to see what can do an how it compares with my own RPC project [Plinker](https://bitbucket.org/plinker/example)

Having worked on it for a few hours it slowly mutated into a remote server database CRUD, with the ability to manage tables, columns and rows.

By default a new /tmp/database.db sqlite file will be used, to manage other tables you simply need only change the `R::setup('...')` dsn.

**Unlike Plinker, there is absolutely no security implemented into the extension, so this is only useful for non sensitive data.**

**Uses** 

 - [Yet Another RPC Framework](http://php.net/manual/en/book.yar.php) (PECL extension)
 - [RedBeanPHP](http://www.redbeanphp.com) for endpoint database operations.

**To install the extension:**

    sudo apt-get install php5-dev libcurl4-gnutls-dev
    sudo pecl install yar

Then create a new extension apache config file for `yar.so`

    echo "extension=yar.so" > /etc/php5/apache2/conf.d/yar.ini

Restart apache...

**Screenshots**

![1.png](https://bitbucket.org/repo/AB97Kz/images/420884455-1.png)
![2.png](https://bitbucket.org/repo/AB97Kz/images/404006128-2.png)
![3.png](https://bitbucket.org/repo/AB97Kz/images/1043424688-3.png)
![4.png](https://bitbucket.org/repo/AB97Kz/images/1533580200-4.png)
![5.png](https://bitbucket.org/repo/AB97Kz/images/689375255-5.png)

**IMO**

[Plinker](https://bitbucket.org/plinker/example) is better, way more secure, namespaced and code accessible, 100% PHP so no extension required and does the same thing and more.