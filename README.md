## Ingress PHP tools for Google's Ingress (game for Android)

In order to install all dependencies you have to php [composer](http://getcomposer.org/ "Composer").phar install, which will create the vendor dir filled with dependencies.
After this you can php run.php and start building.
What I have by now is a fully working login (Ingress\Crawl::login()) which will redirect to ingress.com/intel.
What I'm seeing in the near future is playing with the Ingress REST api in order to get:

 - portal data
 - user data
 - chat monitoring
 This can go as far as monitoring the COMM channel for a few cities around the world for fresh codes.
 - alerts
 Maybe set an alert for a specific portal you want to keep an eye on and recharge if needed.
