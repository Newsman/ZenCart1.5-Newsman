# Mautic-Newsman - Email Integration
[Newsman](https://www.newsmanapp.com) integration for ZendCart 1.5. Send your customers subscribed to newsletter into NewsMan platform.

# Installation

Installation:
1.  Copy files `newsman.php` & `NewsmanClient.php` and paste to your ZenCart root directory in your `admin` folder
2.  - Edit file `{{zencart_root}}/{{your_admin_folder}}/includes/header_navigation.php`
    - Paste the following code `<li><a target="_blank" href="/admin/newsman/newsman.php">Newsman</a></li>` right under `<ul class="nav navbar-nav">`

2.  Click on `Newsman` on your navigation bar in ADMIN panel

# Setup

1. Fill in your Newsman SMTP API KEY and User ID and click `Save`
2. If credentials are correct, select a list and click `Save`
3. Synchronize your customers with active newsletter by clicking `Sync Now`


![](https://raw.githubusercontent.com/Newsman/Mautic-Newsman/master/assets/1.png)
